<?php namespace JacobBennett\Pjax;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\DomCrawler\Crawler;

class PjaxMiddleware {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
        /** @var $response Response */
        $response = $next($request);

        // Only handle non-redirections
        if (!$response->isRedirection()) {
            // Must be a pjax-request
            if ($request->pjax()) {
                $crawler = new Crawler($response->getContent());

                // Filter to title (in order to update the browser title bar)
                $response_title = $crawler->filter('head > title');

                // Filter to bodyClass
                $response_body_class = $crawler->filter('body')->extract(array('class'));

                // Set new body class for the response
                $response->header('X-PJAX-BODY-CLASS', $response_body_class);

                // Filter to given container
                $response_container = $crawler->filter($request->header('X-PJAX-CONTAINER'));

                // Container must exist
                if ($response_container->count() != 0) {

                    $title = '';
                    // If a title-attribute exists
                    if ($response_title->count() != 0) {
                        $title = '<title>' . $response_title->html() . '</title>';
                    }

                    // Set new content for the response
                    $response->setContent($title . $response_container->html());
                }

                // Updating address bar with the last URL in case there were redirects
                $response->header('X-PJAX-URL', $request->getRequestUri());
            }
        }

        return $response;
	}

}
