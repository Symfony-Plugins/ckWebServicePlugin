<?php
/**
 * This file is part of the ckWebServicePlugin
 *
 * @package   ckWebServicePlugin
 * @author    Christian Kerl <christian-kerl@web.de>
 * @copyright Copyright (c) 2008, Christian Kerl
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   SVN: $Id$
 */

/**
 * ckWebServiceController is the endpoint for the webservice interface of your web application.
 *
 * @package    ckWebServicePlugin
 * @subpackage controller
 * @author     Christian Kerl <christian-kerl@web.de>
 */
class ckWebServiceController extends sfController
{
  protected $soap_server = null;

  /**
   * Initializes this controller.
   *
   * @param sfContext $context A sfContext implementation instance
   */
  public function initialize($context)
  {
    parent::initialize($context);

    //don't render anything
    $this->setRenderMode(sfView::RENDER_NONE);
  }

  /**
   * Wrapper method around ckWebServiceController::handle() for compatibility with standard controller generator.
   *
   * @see function handle
   */
  public function dispatch()
  {
    $this->handle();
  }

  /**
   * Starts the soap server to handle webservice requests.
   *
   */
  public function handle()
  {
    //retrieve configuration
    $wsdl = sfConfig::get('app_ck_web_service_plugin_wsdl');

    $options = sfConfig::get('app_ck_web_service_plugin_soap_options', array());
    $handler = sfConfig::get('app_ck_web_service_plugin_handler', 'ckSoapHandler');
    $persist = sfConfig::get('app_ck_web_service_plugin_persist', SOAP_PERSISTENCE_SESSION);

    if (sfConfig::get('sf_logging_enabled'))
    {
      sfContext::getInstance()->getLogger()->info(sprintf('{%s} Starting SoapServer with \'%s\' and Handler \'%s\'.', __CLASS__, $wsdl, $handler));
    }

    //construct the server
    $this->soap_server = new SoapServer($wsdl, $options);

    //set the handler
    $this->soap_server->setClass($handler);

    //set the persistence mode
    $this->soap_server->setPersistence($persist);

    //start the server
    $this->soap_server->handle();
  }

  /**
   * Redirects webservice methods to a module action, to work the method name must follow the scheme MODULE_ACTION.
   * If only MODULE is provided, the standard action 'index' will be used.
   *
   * @param string $method    The method name
   * @param array  $arguments The method arguments
   *
   * @return mixed            The result of the called action
   *
   * @see function invokeSoapEnabledAction
   */
  public function __call($method, $arguments)
  {
    $method = explode('_', $method);

    $moduleName = $method[0];
    $actionName = isset($method[1]) && strlen($method[1]) > 0 ? $method[1] : 'index';

    return $this->invokeSoapEnabledAction($moduleName, $actionName, $arguments);
  }

  /**
   * Invokes an action requested by a webservice call and returns the action result.
   *
   * @param string $moduleName A module name
   * @param string $actionName An action name
   * @param array  $parameters The paramters to pass to the action
   *
   * @throws SoapFault thrown if any exception is thrown during the action call.
   *
   * @return mixed The result of the called action
   */
  public function invokeSoapEnabledAction($moduleName, $actionName, $parameters)
  {
    $moduleName = strtolower($moduleName);
    $actionName = strtolower($actionName);

    $request = $this->getContext()->getRequest();

    $request->setParameter('param', $parameters, 'ckWebServicePlugin');

    try
    {
      if (sfConfig::get('sf_logging_enabled'))
      {
        sfContext::getInstance()->getLogger()->info(sprintf('{%s} Forwarding to \'%s/%s\'.', __CLASS__, $moduleName, $actionName));
      }

      //use forward to invoke the action, so we have to pass the filter chain
      $this->forward($moduleName, $actionName);

      //get the function which retrieves the action result
      $soapResultCallback = sfConfig::get('app_ck_web_service_plugin_result_callback', 'getSoapResult');

      //get the last executed action
      $actionInstance = $this->getActionStack()->getLastEntry()->getActionInstance();

      //if we have been redirected to the 404 module, we raise an exception
      if($actionInstance->getModuleName() == sfConfig::get('sf_error_404_module') && $actionInstance->getActionName() == sfConfig::get('sf_error_404_action'))
      {
        throw new sfError404Exception(sprintf('{%s} SoapFunction \'%s_%s\' not found.', __CLASS__, $moduleName, $actionName));
      }

      //check if we are able to call the result getter
      if(is_callable(array($actionInstance, $soapResultCallback)))
      {
        if (sfConfig::get('sf_logging_enabled'))
        {
          sfContext::getInstance()->getLogger()->info(sprintf('{%s} Calling soapResultCallback \'%s\'.', __CLASS__, $soapResultCallback));
        }

        //return the result of our action
        return $actionInstance->$soapResultCallback();
      }
      else
      {
        return;
      }
    }
    catch(Exception $e)
    {
      //we return all exceptions as soap faults to the remote caller
      throw new SoapFault('1', $e->getMessage(), '', $e->getTraceAsString());
    }
  }
}