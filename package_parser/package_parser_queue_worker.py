#!/usr/bin/env python
import sys, getopt
import pika
import time
import requests
import json
from datetime import datetime
import config as config

class Package_Parser_Queue_Worker:
    API_USERNAME = ""
    API_TOKEN = ""

    def callback(self, ch, method, properties, body):
        package_name = body

        try:
            repo_url = self.get_repo_url(package_name)
        except:
            print "Something went wrong with parsing repo url for this package: %s"%package_name
            ch.basic_reject(method.delivery_tag, requeue=True)
            return

        if repo_url == "":
            # print "Error parsing repository url for package '%s'"%package_name
            ch.basic_ack(delivery_tag = method.delivery_tag)
            return

        #Some of the packages have their repository hosted on other sites eg bitbucket, so we are ignoring them!
        if "github" not in repo_url:
            # print "Package '%s' does not exist on github"%package_name
            ch.basic_ack(delivery_tag = method.delivery_tag)
            return

        #extracting repo name
        repo_name =  repo_url.replace('https://github.com/', '').replace("http://github.com/", "")
        contributors_url = "https://api.github.com/repos/%s/contributors"%repo_name

        try:
            req = requests.get(contributors_url, auth=(self.API_USERNAME, self.API_TOKEN))
        except:
            print "Something went wrong with getting contributors for this package: %s"%contributors_url
            ch.basic_reject(method.delivery_tag, requeue=True)
            return

        if req.status_code == 200:
            contributors = [user["login"] for user in req.json()]
            #Adding the package and the contributord to another queue. The persistor queue is FIFO and only one worker should handle it so we can handle duplicate entries in DB.
            try:
                self.send_to_persistor_queue(package_name, contributors_url, contributors)
            except:
                print "Something went wrong with adding package '%s' to persistor queue"%package_name
                ch.basic_reject(method.delivery_tag, requeue=True)
                return

            ch.basic_ack(delivery_tag = method.delivery_tag)
        elif req.status_code == 403:
            rate_limit_reset = int(req.headers["X-RateLimit-Reset"])
            reset_time = datetime.fromtimestamp(rate_limit_reset)
            print "Reached api cal limit. wiating until: %s"%reset_time.strftime("%Y-%m-%d %H:%M:%S")
            time_delta = reset_time - datetime.now()

            ch._connection.sleep(time_delta.total_seconds() + 60)
            # rejecting the message so it goes back to the queue and it could be processed after rate limit resets.
            ch.basic_reject(method.delivery_tag, requeue=True)
        elif req.status_code == 404:
            # print "Package '%s' does not exist on github anymore!"%(package_name)
            ch.basic_ack(delivery_tag = method.delivery_tag)
        else:
            # print "Error parsing package: %s - url: %s"%(package_name, contributors_url)
            ch.basic_ack(delivery_tag = method.delivery_tag)

    def send_to_persistor_queue(self, package_name, contributors_url, contributors):
        connection = pika.BlockingConnection(pika.ConnectionParameters(host=config.RABBITMQ_HOST))
        channel = connection.channel()

        message = {}
        message["package_name"] = package_name
        message["contributors_url"] = contributors_url
        message["contributors"] = contributors

        channel.queue_declare(queue=config.PACKAGE_PERSISTOR_QUEUE_NAME, durable=True)

        channel.basic_publish(exchange='', routing_key=config.PACKAGE_PERSISTOR_QUEUE_NAME, body=json.dumps(message), 
                              properties=pika.BasicProperties(
                                 delivery_mode = 2, # make message persistent
                              ))
        connection.close()

    def get_repo_url(self, package_name):
        url = "https://packagist.org/packages/%s.json"%package_name

        req = requests.get(url)
        
        response_json = req.json()
        try:
            return response_json["package"]["repository"]
        except:
            return ""

    def start(self, api_username, api_token):
        self.API_USERNAME = api_username
        self.API_TOKEN = api_token
        connection = pika.BlockingConnection(pika.ConnectionParameters(host=config.RABBITMQ_HOST))
        channel = connection.channel()

        channel.queue_declare(queue=config.PACKAGE_PARSER_QUEUE_NAME, durable=True)
        
        channel.basic_qos(prefetch_count=1)
        channel.basic_consume(self.callback, queue=config.PACKAGE_PARSER_QUEUE_NAME)

        channel.start_consuming()


def main(argv):
    api_username = ""
    api_token = ""
    try:
        opts, args = getopt.getopt(argv,"u:t:",["api_username=","api_token="])
    except getopt.GetoptError:
        api_username = ""
        api_token = ""
    for opt, arg in opts:
        if opt in ("-u", "--api_username"):
            api_username = arg
        elif opt in ("-t", "--api_token"):
            api_token = arg

    worker = Package_Parser_Queue_Worker()
    worker.start(api_username, api_token)        
 
if __name__ == "__main__":
   main(sys.argv[1:])