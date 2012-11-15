<?php
namespace Blocks;

/**
 *
 */
class UrlManager extends \CUrlManager
{
	private $_templateVariables = array();

	public $cpRoutes;
	public $pathParam;

	/**
	 *
	 */
	public function init()
	{
		parent::init();

		// set this to false so extra query string parameters don't get the path treatment
		$this->appendParams = false;

		// makes more sense to set in HttpRequest
		if (blx()->request->getUrlFormat() == UrlFormat::PathInfo)
		{
			$this->setUrlFormat(static::PATH_FORMAT);
		}
		else
		{
			$this->setUrlFormat(static::GET_FORMAT);
		}
	}

	/**
	 * @return null
	 */
	public function processTemplateMatching()
	{
		// we'll never have a db entry match on a control panel request
		if (blx()->isInstalled() && !blx()->request->isCpRequest())
		{
			if (($path = $this->matchPage()) !== false)
			{
				return $path;
			}

			if (($path = $this->matchEntry()) !== false)
			{
				return $path;
			}
		}

		if (($path = $this->matchRoute()) !== false)
		{
			return $path;
		}
		else
		{
			return $this->matchTemplatePath();
		}
	}

	/**
	 * @return array Any variables that should be passed into the matched template
	 */
	public function getTemplateVariables()
	{
		return $this->_templateVariables;
	}

	/**
	 * Attempts to match a request with a page in the database.
	 *
	 * @return bool The URI if a match was found, false otherwise.
	 */
	public function matchPage()
	{
		$page = blx()->pages->getPageByUri(blx()->request->getPath());

		if ($page)
		{
			$this->_templateVariables['page'] = $page;
			return $page->uri;
		}

		return false;
	}

	/**
	 * Attempts to match a request with an entry in the database.
	 *
	 * @return bool The URI if a match was found, false otherwise.
	 */
	public function matchEntry()
	{
		$criteria = new EntryCriteria();
		$criteria->uri = blx()->request->getPath();
		$criteria->includeContent = true;
		$entry = blx()->entries->findEntry($criteria);

		if ($entry)
		{
			$this->_templateVariables['entry'] = $entry;
			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				return $entry->getSection()->template;
			}
			else
			{
				return 'blog/_entry';
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function matchRoute()
	{
		if (blx()->request->isCpRequest())
		{
			// Check the Blocks predefined routes.
			foreach ($this->cpRoutes as $pattern => $template)
			{
				if ($this->_matchRouteInternal($pattern))
				{
					return $template;
				}
			}

			// As a last ditch to match routes, check to see if any plugins have routes registered that will match.
			$pluginCpRoutes = blx()->plugins->callHook('registerCpRoutes');
			foreach ($pluginCpRoutes as $pluginRoutes)
			{
				foreach ($pluginRoutes as $pattern => $template)
				{
					if ($this->_matchRouteInternal($pattern))
					{
						return $template;
					}
				}
			}
		}
		else
		{
			// Check the user-defined routes
			$siteRoutes = blx()->routes->getAllRoutes();

			foreach ($siteRoutes as $route)
			{
				if ($this->_matchRouteInternal($route->urlPattern))
				{
					return $route->template;
				}
			}
		}

		return false;
	}

	/**
	 * @param $urlPattern
	 * @return bool
	 */
	private function _matchRouteInternal($urlPattern)
	{
		// Does it match?
		if (preg_match('/^'.$urlPattern.'$/', blx()->request->getPath(), $match))
		{
			// Set any capture variables
			foreach ($match as $key => $value)
			{
				if (!is_numeric($key))
				{
					$this->_templateVariables[$key] = $value;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function matchTemplatePath()
	{
		// Make sure they're not trying to access a private template
		if (!blx()->request->isAjaxRequest())
		{
			foreach (blx()->request->getSegments() as $requestPathSeg)
			{
				if (isset($requestPathSeg[0]) && $requestPathSeg[0] == '_')
				{
					return false;
				}
			}
		}

		return blx()->request->getPath();
	}
}
