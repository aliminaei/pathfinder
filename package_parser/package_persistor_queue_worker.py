import sys
import pika
import time
import json
from subprocess import check_call
import config as config

class Package_Persistor_Queue_Worker:
    def callback(self, ch, method, properties, body):
        print check_call("php %s/bin/console app:package:persist '%s'"%(config.PROJECT_ROOT_PATH, body), shell=True)

        ch.basic_ack(delivery_tag = method.delivery_tag)

    def start(self):
        connection = pika.BlockingConnection(pika.ConnectionParameters(host=config.RABBITMQ_HOST))
        channel = connection.channel()

        channel.queue_declare(queue=config.PACKAGE_PERSISTOR_QUEUE_NAME, durable=True)
        channel.basic_qos(prefetch_count=1)
        channel.basic_consume(self.callback, queue=config.PACKAGE_PERSISTOR_QUEUE_NAME)

        channel.start_consuming()

def main(argv):
    worker = Package_Persistor_Queue_Worker()
    worker.start()        
 
if __name__ == "__main__":
   main(sys.argv[1:])