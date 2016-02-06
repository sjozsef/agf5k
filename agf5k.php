<?php 

/**
 * AutoGirlfriend5000
 * This script sends a random poke as text message to your girlfriend via SeeMe Gateway.
 * 
 * @author: sjozsef
 * @license: MIT
 */

require( dirname( __FILE__ ) . '/config.php' );
require( dirname( __FILE__ ) . '/SeeMeGateway.php');

class AutoGirlfriend5000 
{
    private $cfg;
    
    public function __construct( $cfg )
    {
        $this->cfg = $cfg;
    }
    
    private function getRandom( $max )
    {
        return rand( 0, $max );
    }
    
    private function process( $msg )
    {
        preg_match('/\%(.*?)\%/', $msg, $match);
        
        if( $match && count( $match ) == 2 )
        {
            $results = $this->cfg[$match[1]];
            $max = count( $results );
            $result = $this->getRandomElement( $results );
            
            return str_replace( $match[0], $result, $msg);
        }
        
        return $msg;
    }
    
    private function getRandomElement( &$array )
    {
        $max = count( $array ) - 1;
        $idx = $this->getRandom( $max );
        return $array[$idx];
    }
    
    private function getProcessedRandomElement( &$array )
    {
        
        return $this->process( $this->getRandomElement( $array ) );
    }
    
    private function generateMessage()
    {
        $message = '';
        
        if( is_array( $this->cfg ) )
        {
            foreach( $this->cfg['msg'] as &$part)
            {
                if( !is_array( $part ) || empty( $part ) )
                {
                    continue;
                }
                
                $message .= $this->getProcessedRandomElement( $part );
            }
        }
        
        return $message;
    }
    
    private function send( $msg )
    {
        $sm = new SeeMeGateway( $this->cfg['api_key'] );
        
        if($sm)
        {
            $sm->sendSMS( 
                $this->cfg['number'],
                $msg
            );
        }
    }
    
    public function run()
    {
        $msg = $this->generateMessage();
        $this->send( $msg );
        return $msg;
    }
}

$agf5k = new AutoGirlfriend5000( $agf );

var_dump ($agf5k->run() );