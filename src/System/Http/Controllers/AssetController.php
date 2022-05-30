<?php

namespace Igniter\System\Http\Controllers;

use Exception;
use Igniter\System\Exception\ErrorHandler;
use Igniter\System\Facades\Assets;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AssetController extends Controller
{
    public function __invoke(Request $request, $asset = null)
    {
        try {
            $parts = explode('-', $asset);
            $cacheKey = $parts[0];

            return Assets::combineGetContents($cacheKey);
        }
        catch (Exception $ex) {
            $errorMessage = ErrorHandler::getDetailedMessage($ex);

            return '/* '.e($errorMessage).' */';
        }
    }
}
