import pika
import sys
import requests
import config as config

class Package_Parser_Task:

    def start(self):
        connection = pika.BlockingConnection(pika.ConnectionParameters(host=config.RABBITMQ_HOST))
        channel = connection.channel()

        channel.queue_declare(queue=config.PACKAGE_PARSER_QUEUE_NAME, durable=True)

        req = requests.get('https://packagist.org/packages/list.json')
        data = req.json()
        packages = data["packageNames"]

        for package in packages:
            channel.basic_publish(exchange='', routing_key=config.PACKAGE_PARSER_QUEUE_NAME, body=package,
                                  properties=pika.BasicProperties(
                                     delivery_mode = 2, # make message persistent
                                  ))
            print("Package '%s' has been added to the parser queue." % package)
        connection.close()

def main(argv):
    task = Package_Parser_Task()
    task.start()        
 
if __name__ == "__main__":
   main(sys.argv[1:])