<?php

namespace SilverStripe\Control;

use SilverStripe\ORM\DataModel;
use SilverStripe\Core\ClassInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Triggers a call to flush() on all implementors of Flushable.
 */
class FlushRequestFilter implements RequestFilter {

	/**
	 * @inheritdoc
	 *
	 * @param HTTPRequest $request
	 * @param Session $session
	 * @param DataModel $model
	 *
	 * @return bool
	 */
	public function preRequest(Request $request, Session $session, DataModel $model) {
		if($request->query->get('flush') !== null) {
			foreach(ClassInfo::implementorsOf('SilverStripe\\Core\\Flushable') as $class) {
				$class::flush();
			}
		}

		return true;
	}

	/**
	 * @inheritdoc
	 *
	 * @param HTTPRequest $request
	 * @param HTTPResponse $response
	 * @param DataModel $model
	 *
	 * @return bool
	 */
	public function postRequest(Request $request, Response $response, DataModel $model) {
		return true;
	}

}
