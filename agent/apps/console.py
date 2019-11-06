#coding=  utf-8
import os
import sys
import time
import pika
import json
import pdb
from frame.lib.Config import *

class console(object) :
    def execute(self) :
        '''
        while True:
        # print '%s:hello world\n' % (time.ctime(),)
            sys.stdout.write('%s:hello world\n' % (time.ctime(),))
            sys.stdout.flush()
            time.sleep(2)
        '''
        rmq_url = Config.get('vnet-agent.rabbitmq.mq_url')
        exchange = 'agent'
        queue= '10.200.2.162-agent'
        key='10.200.2.162-agent'
        classname = 'Netcontrol'
        method = 'handle_data'
        rmq = RabbitMQ(rmq_url,classname,method)
        rmq.subscribe(classname,method,exchange,queue,key)

class RabbitMQ(object):
    def __init__(self,url,classname,method,path=None):
        self.url = url
        self.classname=classname
        self.method=method
        if path is not None:
            self.path=path
            #self.receive = Rmqreceive(self.classname,self.method,self.path)
        else:
            self.path='apps'
            #self.receive = Rmqreceive(self.classname,self.method,self.path)
        self.connection = None
        self.exchange= None
        self.queue= None
        self.exchange_name= 'default1'
        self.queue_name='default1'
        self.route_key=''
        self.exchange_type= 1
        self.durable= 1
    @staticmethod
    def check_json_format(data):
        if isinstance(data,str):
            try:
                json.loads(data,encoding='utf-8')
            except ValueError:
                return False
            return True
        else:
            return False
    def connect(self):
        self.connection = pika.BlockingConnection(pika.URLParameters(self.url))
        return self.connection

    def create_queue(self,exchange,queue=None,route_key=None,exchange_type=None,flag=None):
        if exchange is not None:
            self.exchange_name=exchange
            self.queue_name=exchange
        if exchange_type is not None:
            self.exchange_type = exchange_type
        if flag is not None:
            self.durable = flag
        if queue is not None:
            self.queue_name=queue
        if route_key is not None:
            self.route_key = route_key

        self.exchange = self.connect().channel()
        if self.exchange_type == 1 :
            self.exchange.exchange_declare(exchange=self.exchange_name,exchange_type='direct')
        elif self.exchange_type == 2 :
            self.exchange.exchange_declare(exchange=self.exchange_name,exchange_type='fanout')
        elif self.exchange_type == 3 :
            self.exchange.exchange_declare(exchange=self.exchange_name,exchange_type='topic')
        else:
            return False
        if self.durable==1:
            self.queue = self.exchange.queue_declare(queue=self.queue_name,durable=True)
        else:
            self.queue = self.exchange.queue_declare(queue=self.queue_name)
        self.exchange.queue_bind(exchange=self.exchange_name,queue=self.queue_name,routing_key=self.route_key)

    def sendmsg(self,msg,exchange,queue=None,route_key=None,exchange_type=None,flag=None):
        if self.check_json_format(msg) :
            data = msg.strip()
        else:
            data = json.dumps(msg)
        self.create_queue(exchange,queue,route_key,exchange_type,flag)
        if self.durable==1:
            self.exchange.basic_publish(exchange=self.exchange_name,routing_key=self.route_key,body=data,properties=pika.BasicProperties(delivery_mode=2,))
        else:
            self.exchange.basic_publish(exchange=self.exchange_name,routing_key=self.route_key,body=data)
        self.close()

    def subscribe(self,cls,fun,exchange,queue=None,route_key=None,exchange_type=None,flag=None):
        self.create_queue(exchange,queue,route_key,exchange_type,flag)
        self.exchange.basic_consume(queue,self.callback,True)
        self.exchange.start_consuming()

    def close(self):
        if self.connection is not None:
            self.connection.close()
    def callback(self,ch,method,properties,body):
        data = body
        self.make_task(data)
        
    def make_task(self,data):
        self.im_obj = self.path+'.'+self.classname
        self.im_cls = __import__(self.im_obj,fromlist=('*'))
        self._class = getattr(self.im_cls,self.classname)
        self.obj = self._class()
        self.mtd = getattr(self.obj,self.method)
        if data is not None:
            self.mtd(data)
        else:
            self.mtd()
'''
if __name__=='__main__':
    message=json.dumps({"network":"add","brname":"br-2ad3fdeb49","mode":"vlan","vlanid":"1112","host":"10.200.2.162"})
    exchange = 'agent'
    queue= '10.200.2.162-agent'
    key='10.200.2.162-agent'
    rmq_url = 'amqp://vnet:123456@10.200.2.163:5672/%2F'
    #rmq_url = ''
    classname = 'Netcontrol'
    method = 'handle_data'
    rmq = RabbitMQ(rmq_url,classname,method)
    #rmq.sendmsg(message,exchange,queue,key)
    rmq.subscribe(classname,method,exchange,queue,key)
'''
