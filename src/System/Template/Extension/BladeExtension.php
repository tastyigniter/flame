<?php

namespace Igniter\System\Template\Extension;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\ViewFinderInterface;

class BladeExtension
{
    public function register()
    {
        Blade::directive('mainauth', [$this, 'compilesMainAuth']);
        Blade::directive('endmainauth', [$this, 'compilesEndMainAuth']);
        Blade::directive('adminauth', [$this, 'compilesAdminAuth']);
        Blade::directive('endadminauth', [$this, 'compilesEndAdminAuth']);

        Blade::directive('styles', [$this, 'compilesStyles']);
        Blade::directive('scripts', [$this, 'compilesScripts']);

        Blade::directive('partial', [$this, 'compilesPartial']);
        Blade::directive('partialIf', [$this, 'compilesPartialIf']);
        Blade::directive('partialWhen', [$this, 'compilesPartialWhen']);
        Blade::directive('partialUnless', [$this, 'compilesPartialUnless']);
        Blade::directive('partialFirst', [$this, 'compilesPartialFirst']);

        Blade::directive('componentPartial', [$this, 'compilesComponentPartial']);
        Blade::directive('componentPartialIf', [$this, 'compilesComponentPartialIf']);
        Blade::directive('themePage', [$this, 'compilesPage']);
        Blade::directive('themeContent', [$this, 'compilesThemeContent']);
        Blade::directive('themePartial', [$this, 'compilesThemePartial']);
        Blade::directive('themePartialIf', [$this, 'compilesThemePartialIf']);
    }

    //
    //
    //

    public function compilesMainAuth($expression)
    {
        return "<?php if(\Igniter\Main\Facades\Auth::check()): ?>";
    }

    public function compilesAdminAuth($expression)
    {
        return "<?php if(\Igniter\Admin\Facades\AdminAuth::check()): ?>";
    }

    public function compilesEndMainAuth()
    {
        return "<?php endif ?>";
    }

    public function compilesEndAdminAuth()
    {
        return "<?php endif ?>";
    }

    public function compilesStyles($expression)
    {
        return "<?php echo \Igniter\System\Facades\Assets::getCss(); ?>\n".
            "<?php echo \$__env->yieldPushContent('styles'); ?>";
    }

    public function compilesScripts($expression)
    {
        return "<?php echo \Igniter\System\Facades\Assets::getJs(); ?>\n".
            "<?php echo \$__env->yieldPushContent('scripts'); ?>";
    }

    public function compilesPartial($expression)
    {
        $expression = $this->stripParentheses($expression);
        [$partial, $data] = strpos($expression, ',') !== false
            ? array_map('trim', explode(',', trim($expression, '()'), 2)) + ['', '[]']
            : [trim($expression, '()'), '[]'];

        $partial = $this->stripQuotes($partial);

        $partial = $this->guessViewName($partial, '_partials.');

        $expression = sprintf('%s, %s', '"'.$partial.'"', $data);

        return "<?php echo \$__env->make({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    public function compilesPartialIf($expression)
    {
        $expression = $this->stripParentheses($expression);
        [$partial, $data] = strpos($expression, ',') !== false
            ? array_map('trim', explode(',', trim($expression, '()'), 2)) + ['', '[]']
            : [trim($expression, '()'), '[]'];

        $partial = $this->stripQuotes($partial);

        $partial = $this->guessViewName($partial, '_partials.');

        $expression = sprintf('%s, %s', '"'.$partial.'"', $data);

        return "<?php if (\$__env->exists({$expression})) echo \$__env->make({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    public function compilesPartialWhen($expression)
    {
        $expression = $this->stripParentheses($expression);
        $expression = $this->appendPartialPath($expression);

        return "<?php echo \$__env->renderWhen($expression, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    public function compilesPartialUnless($expression)
    {
        $expression = $this->stripParentheses($expression);
        $expression = $this->appendPartialPath($expression);

        return "<?php echo \$__env->renderWhen(! $expression, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    public function compilesPartialFirst($expression)
    {
        $expression = $this->stripParentheses($expression);
        $expression = $this->appendPartialPath($expression);

        return "<?php echo \$__env->first({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    //
    //
    //

    public function compilesThemeContent($expression)
    {
        return "<?php echo controller()->renderContent({$expression}); ?>";
    }

    public function compilesComponentPartial($expression)
    {
        return "<?php echo controller()->renderComponent({$expression}); ?>";
    }

    public function compilesComponentPartialIf($expression)
    {
        return "<?php if (controller()->hasComponent({$expression})) echo controller()->renderComponent({$expression}); ?>";
    }

    public function compilesPage($expression)
    {
        return '<?php echo controller()->renderPage(); ?>';
    }

    public function compilesThemePartial($expression)
    {
        return "<?php echo controller()->renderPartial({$expression}); ?>";
    }

    public function compilesThemePartialIf($expression)
    {
        return "<?php if (controller()->hasComponent({$expression})) echo controller()->renderPartial({$expression}); ?>";
    }

    //
    //
    //

    public function stripQuotes($string)
    {
        return preg_replace("/[\"\']/", '', $string);
    }

    public function stripParentheses($expression)
    {
        if (Str::startsWith($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }

    public function appendPartialPath($expression)
    {
        [$condition, $partial, $data] = strpos($expression, ',') !== false
            ? array_map('trim', explode(',', trim($expression, '()'), 2)) + ['', '', '[]']
            : [trim($expression, '()'), '', '[]'];

        $partial = $this->stripQuotes($partial);

        $partial = $this->guessViewName($partial, '_partials.');

        return sprintf('%s, %s, %s', $condition, '"'.$partial.'"', $data);
    }

    public function guessViewName($name, $prefix = 'components.')
    {
        if (!Str::endsWith($prefix, '.')) {
            $prefix .= '.';
        }

        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (str_contains($name, $delimiter)) {
            return Str::replaceFirst($delimiter, $delimiter.$prefix, $name);
        }

        return $prefix.$name;
    }
}
