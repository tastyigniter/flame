<?php

namespace Igniter\Admin\Classes;

use Exception;
use Igniter\Admin\Facades\Admin;
use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Facades\AdminLocation;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Widgets\Menu;
use Igniter\Admin\Widgets\Toolbar;
use Igniter\Flame\Exception\AjaxException;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Exception\ValidationException;
use Igniter\Flame\Flash\Facades\Flash;
use Igniter\Main\Widgets\MediaManager;
use Igniter\System\Exception\ErrorHandler;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;

class AdminController extends Controller
{
    use \Igniter\Admin\Traits\HasAuthentication;
    use \Igniter\Admin\Traits\ControllerUtils;
    use \Igniter\Admin\Traits\ValidatesForm;
    use \Igniter\Admin\Traits\WidgetMaker;
    use \Igniter\System\Traits\AssetMaker;
    use \Igniter\System\Traits\ConfigMaker;
    use \Igniter\System\Traits\SessionMaker;
    use \Igniter\System\Traits\ViewMaker;
    use \Igniter\Flame\Traits\EventEmitter;
    use \Igniter\Flame\Traits\ExtendableTrait;

    /**
     * @var object Object used for storing a fatal error.
     */
    protected $fatalError;

    /**
     * @var \Igniter\Admin\Classes\BaseWidget[] A list of BaseWidget objects used on this page
     */
    public $widgets = [];

    /**
     * @var bool Prevents the automatic view display.
     */
    public $suppressView = false;

    /**
     * @var string Permission required to view this page.
     * ex. Admin.Banners.Access
     */
    protected $requiredPermissions;

    /**
     * @var string Page title
     */
    public $pageTitle;

    /**
     * @var string Body class property used for customising the layout on a controller basis.
     */
    public $bodyClass;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->setRequiredProperties();

        // Define layout and view paths
        $this->definePaths();

        $this->extendableConstruct();
    }

    protected function definePaths()
    {
        $this->layout = $this->layout ?: 'default';

        $parts = explode('\\', strtolower(get_called_class()));
        $className = array_pop($parts);
        $namespace = implode('.', array_slice($parts, 0, 2));

        // Add paths from the extension / module context
        $this->viewPath[] = $namespace.'::'.$className;
        $this->viewPath[] = $namespace.'::';
        $this->viewPath[] = 'igniter.admin::'.$className;
        $this->viewPath[] = 'igniter.admin::';

        // Add layout paths from the extension / module context
        $this->layoutPath[] = $namespace.'::_layouts';
        $this->layoutPath[] = 'igniter.admin::_layouts';

        // Add partial paths from the extension / module context
        // We will also make sure the admin module context is always present
        $this->partialPath[] = $namespace.'::_partials.'.$className;
        $this->partialPath[] = 'igniter.admin::_partials.'.$className;
        $this->partialPath[] = $namespace.'::_partials';
        $this->partialPath[] = 'igniter.admin::_partials';
        $this->partialPath[] = 'igniter.system::_partials';

        $this->configPath[] = $namespace.'::models';
        $this->configPath[] = 'igniter::models/admin';
        $this->configPath[] = 'igniter::models/system';
        $this->configPath[] = 'igniter::models/main';

        $this->assetPath[] = $namespace.'::';
        $this->assetPath[] = 'igniter::';
        $this->assetPath[] = 'igniter::css';
        $this->assetPath[] = 'igniter::js';
    }

    protected function initialize()
    {
        // Set an instance of the admin user
        $this->setUser(AdminAuth::user());

        $this->fireSystemEvent('admin.controller.beforeInit');

        // Toolbar widget is available on all admin pages
        $toolbar = new Toolbar($this, ['context' => $this->action]);
        $toolbar->bindToController();

        // Media Manager widget is available on all admin pages
        if ($this->currentUser && $this->currentUser->hasPermission('Admin.MediaManager')) {
            $manager = new MediaManager($this, ['alias' => 'mediamanager']);
            $manager->bindToController();
        }

        // Top menu widget is available on all admin pages
        $this->makeMainMenuWidget();

        return $this;
    }

    public function remap($action, $params)
    {
        $this->fireSystemEvent('admin.controller.beforeRemap');

        // Check that user has permission to view this page
        if ($this->requiredPermissions && !$this->authorize($this->requiredPermissions)) {
            return response()->make(request()->ajax()
                ? lang('igniter::admin.alert_user_restricted')
                : $this->makeView('access_denied'), 403
            );
        }

        if ($event = $this->fireSystemEvent('admin.controller.beforeResponse', [$action, $params])) {
            return $event;
        }

        if ($action === '404') {
            return Response::make($this->makeView('404'), 404);
        }

        // Execute post handler and AJAX event
        if (($handlerResponse = $this->processHandlers()) && $handlerResponse !== true) {
            return $handlerResponse;
        }

        // Loads the requested controller action
        $response = $this->execPageAction($action, $params);

        if (!is_string($response))
            return $response;

        // Return response
        return Response::make()->setContent($response);
    }

    protected function execPageAction($action, $params)
    {
        array_unshift($params, $action);

        // Execute the action
        $result = call_user_func_array([$this, $action], array_values($params));

        // Render the controller view if not already loaded
        if (is_null($result) && !$this->suppressView) {
            return $this->makeView($this->fatalError ? 'admin::error' : $action);
        }

        return $result;
    }

    protected function makeMainMenuWidget()
    {
        if (!$this->currentUser)
            return;

        if (AdminMenu::isCollapsed())
            $this->bodyClass .= 'sidebar-collapsed';

        $config = [];
        $config['alias'] = 'mainmenu';
        $config['items'] = AdminMenu::getMainItems();
        $config['context'] = class_basename($this);
        $mainMenuWidget = new Menu($this, $config);
        $mainMenuWidget->bindToController();
    }

    //
    // Handlers
    //

    protected function executePageHandler($handler, $params)
    {
        // Process Widget handler
        if (strpos($handler, '::')) {
            [$widgetName, $handlerName] = explode('::', $handler);

            // Execute the page action so widgets are initialized
            $this->suppressView = true;
            $this->execPageAction($this->action, $this->params);

            if (!isset($this->widgets[$widgetName])) {
                throw new SystemException(sprintf(lang('igniter::admin.alert_widget_not_bound_to_controller'), $widgetName));
            }

            if (($widget = $this->widgets[$widgetName]) && $widget->methodExists($handlerName)) {
                $result = call_user_func_array([$widget, $handlerName], array_values($params));

                return $result ?: true;
            }
        }
        // Process page specific handler (index_onSomething)
        else {
            if (($result = $this->runHandler($handler, $params, $this->action)) !== null)
                return $result;

            $this->suppressView = true;

            $this->execPageAction($this->action, $this->params);

            foreach ((array)$this->widgets as $widget) {
                if ($widget->methodExists($handler)) {
                    $result = call_user_func_array([$widget, $handler], array_values($params));

                    return $result ?: true;
                }
            }
        }

        return false;
    }

    protected function processHandlers()
    {
        if (!$handler = Admin::getAjaxHandler())
            return false;

        try {
            Admin::validateAjaxHandler($handler);

            $partials = Admin::validateAjaxHandlerPartials();

            $response = [];

            $params = $this->params;
            array_unshift($params, $this->action);
            $result = $this->executePageHandler($handler, $params);

            foreach ($partials as $partial) {
                $response[$partial] = $this->makePartial($partial);
            }

            if (request()->ajax()) {
                if ($result instanceof RedirectResponse) {
                    $response[Admin::HANDLER_REDIRECT] = $result->getTargetUrl();
                    $result = null;
                }
                elseif (Flash::messages()->isNotEmpty()) {
                    $response['#notification'] = $this->makePartial('flash');
                }
            }

            if (is_array($result)) {
                $response = array_merge($response, $result);
            }
            elseif (is_string($result)) {
                $response['result'] = $result;
            }
            elseif (is_object($result)) {
                return $result;
            }

            return $response;
        }
        catch (ValidationException $ex) {
            $this->flashValidationErrors($ex->getErrors());

            $response['#notification'] = $this->makePartial('flash');
            $response['X_IGNITER_ERROR_FIELDS'] = $ex->getFields();
//            $response['X_IGNITER_ERROR_MESSAGE'] = $ex->getMessage(); avoid duplicate flash message.

            throw new AjaxException($response);
        }
        catch (MassAssignmentException $ex) {
            throw new ApplicationException(lang('igniter::admin.form.mass_assignment_failed', ['attribute' => $ex->getMessage()]));
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    //
    // Locationable
    //

    public function getUserLocation()
    {
        return AdminLocation::getLocation();
    }

    public function getLocationId()
    {
        return AdminLocation::getId();
    }

    //
    // Helper Methods
    //

    public function pageUrl($path = null, $parameters = [], $secure = null)
    {
        return Admin::url($path, $parameters, $secure);
    }

    public function redirect($path, $status = 302, $headers = [], $secure = null)
    {
        return Admin::redirect($path, $status, $headers, $secure);
    }

    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        return Admin::redirectGuest($path, $status, $headers, $secure);
    }

    public function redirectIntended($path, $status = 302, $headers = [], $secure = null)
    {
        return Admin::redirectIntended($path, $status, $headers, $secure);
    }

    public function redirectBack($status = 302, $headers = [], $fallback = false)
    {
        return Redirect::back($status, $headers, Admin::url($fallback ?: 'dashboard'));
    }

    public function refresh()
    {
        return Redirect::back();
    }

    /**
     * Sets standard page variables in the case of a controller error.
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function handleError(Exception $exception)
    {
        $errorMessage = ErrorHandler::getDetailedMessage($exception);
        $this->fatalError = $errorMessage;
        $this->vars['fatalError'] = $errorMessage;

        flash()->error($errorMessage)->important();
    }

    //
    // Extendable
    //

    public function __get($name)
    {
        return $this->extendableGet($name);
    }

    public function __set($name, $value)
    {
        $this->extendableSet($name, $value);
    }

    public function __call($method, $parameters)
    {
        return $this->extendableCall($method, $parameters);
    }

    public static function __callStatic($name, $params)
    {
        return self::extendableCallStatic($name, $params);
    }

    public static function extend(callable $callback)
    {
        self::extendableExtendCallback($callback);
    }
}
