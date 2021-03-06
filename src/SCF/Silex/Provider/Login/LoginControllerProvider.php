<?php
namespace SCF\Silex\Provider\Login;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class LoginControllerProvider implements ControllerProviderInterface {
    const VALIDATE_CREDENTIALS 	= '/validate-credentials';
    const TOKEN_HEADER_KEY     	= 'X-API-Token';
    const API_HEADER_KEY     	= 'X-API-Key';
    
    private $baseRoute;
    
    public function setBaseRoute($baseRoute) {
        $this->baseRoute = $baseRoute;
        return $this;
    }
    
    public function connect(Application $app) {
        $this->setUpMiddlewares($app);
        return $this->extractControllers($app);
    }
    
    private function extractControllers(Application $app) {
        $controllers = $app['controllers_factory'];

        return $controllers;
    }
    
    public function getValidateCredentialsUrl() {
    	return $this->baseRoute.self::VALIDATE_CREDENTIALS;
    }
    
    private function setUpMiddlewares(Application $app) {
        $app->before(function (Request $request) use ($app) {
	    
	    $app['monolog']->debug('Verificando se o path "'.$request->getPathInfo().'" requer autenticação');

            if ($this->isAuthRequiredForPath($request->getPathInfo())) {
		$app['monolog']->debug('Requer autenticação');

		$app['monolog']->debug('Verificando se o token e apikey informados são válidos');
		$app['monolog']->debug('header['.self::TOKEN_HEADER_KEY.'] = '.$this->getTokenFromRequest($request));
		$app['monolog']->debug('header['.self::API_HEADER_KEY.'] = '.$this->getApiKeyFromRequest($request));

                if (!$this->isValidTokenForApplication($app, $this->getTokenFromRequest($request), $this->getApiKeyFromRequest($request))) {
		    $app['monolog']->debug('Token ou ApiKey não são válidos. Autenticação negada.');
                    throw new AccessDeniedHttpException('Access denied', null, '10.999');
                }
            }
        });
    }
    
    private function getTokenFromRequest(Request $request) {
        return $request->headers->get(self::TOKEN_HEADER_KEY);
    }
    
    private function getApiKeyFromRequest(Request $request) {
    	return $request->headers->get(self::API_HEADER_KEY);
    }
    
    private function isAuthRequiredForPath($path) {
        return !in_array($path, [$this->baseRoute . self::VALIDATE_CREDENTIALS, '/welcome']);
    }
    
    private function isValidTokenForApplication(Application $app, $token, $apiKey) {
        return $app[LoginServiceProvider::AUTH_ISVALID_TOKEN]($token, $apiKey);
    }
}
