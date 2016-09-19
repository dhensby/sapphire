<?php

namespace SilverStripe\Control;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * A {@link HTTPResponse} encapsulated in an exception, which can interrupt the processing flow and be caught by the
 * {@link RequestHandler} and returned to the user.
 *
 * Example Usage:
 * <code>
 * throw new HTTPResponse_Exception('This request was invalid.', 400);
 * throw new HTTPResponse_Exception(new HTTPResponse('There was an internal server error.', 500));
 * </code>
 */
class HTTPResponse_Exception extends Exception
{

    /**
     * @var Response
     */
	protected $response;

	/**
	 * @param Response|string $body Either the plaintext content of the error
	 * message, or an HTTPResponse object representing it. In either case, the
	 * $statusCode and $statusDescription will be the HTTP status of the resulting
	 * response.
	 * @param int $statusCode
	 * @param string $statusDescription
	 * @see HTTPResponse::__construct();
	 */
	public function __construct($body = null, $statusCode = null, $statusDescription = null)
	{
		if ($body instanceof Response) {
			// statusCode and statusDescription should override whatever is passed in the body
			if ($statusCode) {
				$body->setStatusCode($statusCode, $statusDescription);
			}

			$this->setResponse($body);
		} else {
			$response = Response::create($body)->setStatusCode($statusCode, $statusDescription);

			// Error responses should always be considered plaintext, for security reasons
			$response->headers->set('Content-Type', 'text/plain');

			$this->setResponse($response);
		}

		parent::__construct($this->getResponse()->getContent(), $this->getResponse()->getStatusCode());
	}

	/**
	 * @return Response
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @param Response $response
	 */
	public function setResponse(Response $response)
	{
		$this->response = $response;
	}

}
