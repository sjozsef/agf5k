<?php 
/**
 * Auto GirlFriend 5000 configuration
 * 
 * @author sjozsef
 */

$agf = array( 'msg' => array() );

// This is a random part of the message
$agf['msg'][] = 
    array(
        'Hello %name%! ', // %name% will be replaced with a random element of the array $agf['name']
        'Dear %name%! '
    );

// The array containing possible values of %name%
$agf['name'] = 
    array(
        'Lorem',
        'Ipsum'
    );

// Another random part. You can have unlimited parts like this
$agf['msg'][] = 
    array(
        'Lorem ipsum ',
        'Ipsum Lorem ',
        'Dolor '
    );
    
$agf['msg'][] = 
    array( 
        'lorem ',
        'ipsum '
    );
    
$agf['msg'][] = 
    array(
        'lorem.',
        'ipsum.',
        'hiányzol.',
        'szép vagy.'
    );

// Your SeeMe api key
$agf['api_key'] = '';

// Your GFs number
$agf['number'] = '';