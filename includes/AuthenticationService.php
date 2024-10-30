<?php
/**
 * AuthenticationService class file
 * 
 * @author    Emmanuel NINET
 * @copyright (c) 2012 In-Webo Technologies
 * @license   GNU/GPL
 * @package   In-Webo Login Plugin for WordPress
 */

/**
 * authenticate class
 */
require_once 'authenticate.php';
/**
 * authenticateResponse class
 */
require_once 'authenticateResponse.php';
/**
 * authenticateWithIp class
 */
require_once 'authenticateWithIp.php';
/**
 * authenticateWithIpResponse class
 */
require_once 'authenticateWithIpResponse.php';

/**
 * AuthenticationService class
 * 
 *  
 * 
 * @author    {author}
 * @copyright {copyright}
 * @package   {package}
 */
class AuthenticationService extends SoapClient {

  public function AuthenticationService($wsdl = "Authentication.wsdl", $options = array('encoding'=>'UTF-8')) {
    parent::__construct($wsdl, $options);
  }

  /**
   *  
   *
   * @param authenticate $parameters
   * @return authenticateResponse
   */
  public function authenticate(authenticate $parameters) {
    return $this->__call('authenticate', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://service.inwebo.com',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param authenticateWithIp $parameters
   * @return authenticateWithIpResponse
   */
  public function authenticateWithIp(authenticateWithIp $parameters) {
    return $this->__call('authenticateWithIp', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://service.inwebo.com',
            'soapaction' => ''
           )
      );
  }

}

?>
