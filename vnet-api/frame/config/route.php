<?php
return array( 
    'networks' => array(
        ['get','networks','networks/view',''],
        ['get','networks/id','networks/show','id'],
        ['post','networks','networks/add',''],
        ['put','networks/id','networks/update','id'],
        ['delete','networks/id','networks/del','id'],
    ),
    'nodes' => array(
        ['get','nodes','nodes/view',''],
        ['get','nodes/id','nodes/show','id'],
        ['post','nodes','nodes/add',''],
        ['put','nodes/id','nodes/update','id'],
        ['delete','nodes/id','nodes/del','id'],
    ),
    'subnets' => array(
        ['get','subnets','subnets/view',''],
        ['get','subnets/id','subnets/show','id'],
        ['post','subnets','subnets/add',''],
        ['put','subnets/id','subnets/update','id'],
        ['delete','subnets/id','subnets/del','id'],
    ),
    'ports' => array(
        ['get','ports','ports/view',''],
        ['get','ports/id','ports/show','id'],
        ['post','ports','ports/dhcp',''],
        ['put','ports/id','ports/update','id'],
        ['delete','ports/id','ports/del','id'],
    ),
    'fix' => array(
        ['post','fix','fix/create',''],
        ['put','fix/id','fix/update','id'],
        ['delete','fix/id','fix/del','id'],
    ),
);
