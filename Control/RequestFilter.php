<?php

namespace SilverStripe\Control;

use SilverStripe\ORM\DataModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A request filter is an object that's executed before and after a
 * request occurs. By returning 'false' from the preRequest method,
 * request execution will be stopped from continuing
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
interface RequestFilter {

	/**
	 * Filter executed before a request processes
	 *
	 * @param HTTPRequest $request Request container object
	 * @param Session $session Request session
	 * @param DataModel $model Current DataModel
	 * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
	 */
	public function preRequest(Request $request, Session $session, DataModel $model);

	/**
	 * Filter executed AFTER a request
	 *
	 * @param HTTPRequest $request Request container object
	 * @param HTTPResponse $response Response output object
	 * @param DataModel $model Current DataModel
	 * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
	 */
	public function postRequest(Request $request, Response $response, DataModel $model);
}
