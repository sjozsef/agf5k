<?php

define('GATEWAY_URL', 'https://seeme.hu/gateway');

/**
 * 
 * SeeMe SMS Gateway class
 * 
 * @version 2.0.1 SeeMeGateway
 * @copyright 2013, Dream Interactive Kft.
 * @author 2013 Adam Pinter adam.pinter@dream.hu
 * 
 * @link https://seeme.hu
 * 
 */

/**
 * CHANGELOG
 *  1.0.2 sendSMS() method: removed 0 param @ line: 107
 *  1.0.1 http_build_query() added param arg_separator: '&amp;' @ line: 225
 *  2.0.0 uses API key, new parameter validators
 *  2.0.1 reference validator hotfix
 */

class SeeMeGateway {
	
	/**
   	* Gateway calling method file_get_contents or curl
   	* 
   	* @access private
   	* @var string
   	*/
	private $method 			= 'curl', // curl, file_get_contents
	        $apiUrl 			= GATEWAY_URL,
	        $logFileDestination	= false,
	        $format   			= 'json'; // string, json, xml
            
    #xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
	#xx DO NOT EDIT CODE UNDER THIS LINE
	#xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx        
    
    private
            $params   			= array(),
	        $result,
	        $reference,
            $version            = '2.0.1',
            $checksumLength     = 4;
            
	public function __construct( $apiKey, $logFileDestination = false ) {
	
		if(!is_string($apiKey)){
			throw new SeeMeGatewayException('Invalid API key type. Must be string', 1);
		}
	
		$this->params['key'] 	= trim($apiKey);
		
        if(!$this->validateApiKey($this->params['key'])){
			throw new SeeMeGatewayException('Invalid API key', 18);
		}
        
		if($logFileDestination) {
			$this->logFileDestination = $logFileDestination;
        }
	}
	
	/**
   	* Send SMS. Throws an exception on error
   	*
   	* @access public
   	* @param string $number   Mobile phone number in international format (pl. 36201234567)
   	* @param string $message  Message encoded in UTF-8 
   	* @param string $sender   Sender ID
   	* @return void
   	*/
	public function sendSMS( $number, $message, $sender = '', $reference = null, $callbackParams = null, $callbackURL = null ) {
		
		if(!is_string($message)){
			throw new SeeMeGatewayException('Invalid message parameter type. Must be string', 1);
		}
	
		$params = $this->params;
		$params['number']   = trim($number);
		$params['message']  = trim($message);
		
		if(strlen($params['message']) < 1){
		    throw new SeeMeGatewayException('Invalid message parameter.', 1);
		}
		
		if($sender) {
			$params['sender'] = trim($sender);
        }
        
		if($reference) {
			
			if(!is_string($reference) && !is_numeric($reference)){
			    throw new SeeMeGatewayException('Invalid number reference type. Must be string or number', 1);
			}
			
			$params['reference'] = trim($reference);
        }
            
		if($callbackParams){
		   if($callbackParams == "all"){
		   		$params['callback'] = "1,2,3,4,5,6,7,8,9,10";
		   } else if( $this->validateCallbackParams($callbackParams) ) {
		   		$params['callback'] = $callbackParams;
		   } else {
		   		throw new SeeMeGatewayException('Incorrect callback parameter format', 1);
		   }
		}
		   
		if($callbackURL) {
			$params['callbackurl'] = $callbackURL;
        }
		
		if( !is_numeric($params['number']) ){
		  throw new SeeMeGatewayException(
		      "Only numbers are allowed: number",
		      "2"
		    );
		}

		$result = $this->callAPI( $params );
		return $this->parseResult( $result );
	}
	
	public function getBalance() {
	
		$params = $this->params;
		$params['method'] = 'balance';
		
		$result = $this->callAPI( $params );
		return $this->parseResult( $result );
		
	}
  
	public function setIP( $ip ) {
	
	  $params = $this->params;
	  $params['method'] = 'setip';
	  $params['ip']     = trim($ip);
	  
	  if( !$this->validateIP( $ip ) ){
	  	throw new SeeMeGatewayException(
	        "Parameter is invalid: ip",
	        "15"
	      );
	  }
	  
	  $result = $this->callAPI( $params );
	  return $this->parseResult( $result );
	  
	}
	
	/**
   	* Returns the call's result
   	*
   	* @access public
   	* @return string
   	*/
	public function getResult() {
	  return $this->result;
	}
	
	/**
	 * Parse the callback result
	 *
	 * @access private
	 * @param string $result
	 * @return array
	 */
	public function parseResult( $result ) {
	
	  switch ( $this->format ){
	  
	  	case 'string':
	  	
	  		if(!is_string($result)){
		  		throw new Exception("SeeMe Gateway: Wrong return format type. Must be a string");
	  		}
	  	
	  		parse_str( $result, $resultparts );
	  		break;
	  	case 'json':
	  		$resultparts = json_decode($result,true);
	  		break;
	  	case 'xml':
	  		$resultparts = json_decode(json_encode((array)simplexml_load_string($result)),1);
	  		break;
	  	default:
	  		throw new Exception("SeeMe Gateway: Unexpected return format");
	  		break;
	  
	  }
	  
	  
	  $this->result = $resultparts;
	  $this->logToFile($resultparts);
	
	  switch ( @$resultparts['result'] ) {
	
	    case 'OK':
	      // SMS submitted successfully
	      return $resultparts;
	      break;
	
	    case 'ERR':
	      // error during SMS submit
	      throw new SeeMeGatewayException(
	        $resultparts['message'],
	        $resultparts['code']
	      );
	      break;
	
	    default:
	      throw new Exception(
	        'SeeMe Gateway: unimplemented result '.
	        '"' . @$resultparts['code'] . '", ' .
	        '"' . @$resultparts['message'] . '", '.
	        'raw result: "' . $result . '"'
	      );
	      break;
	
	  }
	
	}
	
	private function callAPI( array $params ) {
	
		if(isset($this->format)){
			$params['format'] = $this->format;
		}
		
        $params['apiVersion'] = $this->version; // SeeMe GW api version
        $apiUrl               = $this->apiUrl.'?'.http_build_query( $params, '', '&' );

        $this->logToFile( "----------------------------" );

        $this->logToFile( $this->method . ': ' . $apiUrl );

        switch ( trim($this->method) ) {
            case 'file_get_contents':
                if ( !ini_get('allow_url_fopen') ) {
                    throw new Exception("SeeMe Gateway: can't use allow_url_fopen method.");
                }
                $result = file_get_contents( $apiUrl );
                break;
            case 'curl':
                if ( !extension_loaded('curl') ) {
                    throw new Exception('SeeMe Gateway: CURL not installed on your server');
                }
                $result = $this->callCURL( $apiUrl );
                break;
            default:
                throw new Exception('SeeMe Gateway: unimplemented callingMethod: "' . $this->method . '"');
        }

        if ( $result === false ) {
            throw new Exception( 'SeeMe Gateway: failed to open file_get_contents("' . $apiUrl . '")' );
        }

        return $result;
	}

	private function callCURL( $apiUrl ) {
	
        $cURL = curl_init();

            curl_setopt_array( $cURL, array(
                CURLOPT_URL               => $apiUrl,
                CURLOPT_RETURNTRANSFER    => true,
                CURLOPT_AUTOREFERER       => true,
                CURLOPT_FOLLOWLOCATION    => true,
                // CURLOPT_CONNECTTIMEOUT_MS => 2000,
                // CURLOPT_TIMEOUT           => 10,
                CURLOPT_FAILONERROR       => true,
            ));

        $result     = curl_exec( $cURL );
        $httpcode = curl_getinfo( $cURL, CURLINFO_HTTP_CODE );

        if ( $result === false ) {

            throw new Exception(
                'SeeMe Gateway: CURL ERROR: ' . $httpcode . ', ' . curl_error( $cURL )
            );
        } else {
            return $result;
        }
	}

	/**
   	* Validate callback parameters
   	*
   	* @access private
   	* @param string $params
   	* @return boolean
   	*/
	private function validateCallbackParams($params){
        return preg_match( '/^[0-9]{1,2}(\,[0-9]{1,2})*$/', $params );
	}
	
	/**
   	* Validate IP parameter
   	*
   	* @access private
   	* @param string $ip
   	* @return boolean
   	*/
	private function validateIP($ip){
		return preg_match( "/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $ip);
	}
    
    private function validateApiKey($hash) {
        $key = substr($hash, 0, -$this->checksumLength);
        $checksum = substr($hash, -$this->checksumLength);
        return substr(md5($key), 0, $this->checksumLength) == $checksum;
    }
	
	/**
   	* Log to file
   	*
   	* @access private
   	* @param string $string
   	* @return null
   	*/
	private function logToFile( $string ) {
	
	  if ( $this->logFileDestination ) {
	    $f = fopen( $this->logFileDestination, 'a' );
	    if ( !$f )
	      throw new Exception('SeeMe Gateway: failed to fopen( "' . $this->logFileDestination . '" )' );
	
	    if( is_array($string) ){
	    	foreach($string AS $key=>$value){
	    		fputs( $f, date("Y-m-d H:i:s") . ' - ' . $key . ' => ' . $value . "\n" );
	    	}
	    } else {
	    	fputs( $f, date("Y-m-d H:i:s") . ' - ' . $string . "\n" );
	    }
	
	    fclose( $f );
	  }
	}

}


class SeeMeGatewayException extends Exception {
	public function __construct($response,$errorCode) {
		$this->response		= $response;
		$this->errorCode    = $errorCode;
		parent::__construct( $response, $errorCode );
	}
	public function __toString() {
		return __CLASS__ . ": SEEME_GATEWAY_ERROR #{$this->errorCode}, {$this->response}\n";
	}
}

