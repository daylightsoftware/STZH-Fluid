<?php
namespace TYPO3Fluid\Fluid\Core\ViewHelper\Traits;

use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Compiler\ViewHelperCompiler;
use TYPO3Fluid\Fluid\Core\Exception;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class CompilableWithContentArgumentAndRenderStatic
 *
 * Provides default methods for rendering and compiling
 * any ViewHelper that conforms to the `renderStatic`
 * method pattern but has the added common use case that
 * an argument value must be checked and used instead of
 * the normal render children closure, if that named
 * argument is specified and not empty.
 */
trait CompileWithContentArgumentAndRenderStatic {

    /**
     * Name of variable that contains the value to use
     * instead of render children closure, if specified.
     * If no name is provided here, the first variable
     * registered in `initializeArguments` of the ViewHelper
     * will be used.
     *
     * Note: it is significantly better practice to define
     * this property in your ViewHelper class and so fix it
     * to one particular argument instead of resolving,
     * especially when your ViewHelper is called multiple
     * times within an uncompiled template!
     *
     * @var string
     */
    protected $contentArgumentName;

    /**
     * Default render method to render ViewHelper with
     * first defined optional argument as content.
     *
     * @return string Rendered string
     * @api
     */
    public function render() {
        $argumentName = $this->resolveContentArgumentName();
        $arguments = $this->arguments;
        if (!empty($argumentName) && isset($arguments[$argumentName])) {
            $renderChildrenClosure = function() use ($arguments, $argumentName) { return $arguments[$argumentName]; };
        } else {
            $renderChildrenClosure = call_user_func_array(array($this, 'buildRenderChildrenClosure'), array());
        }
        return self::renderStatic(
            $arguments,
            $renderChildrenClosure,
            $this->renderingContext
        );
    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param ViewHelperNode $node
     * @param TemplateCompiler $compiler
     * @return string
     */
    public function compile(
        $argumentsName,
        $closureName,
        &$initializationPhpCode,
        ViewHelperNode $node,
        TemplateCompiler $compiler
    ) {
        list ($initialization, $execution) = ViewHelperCompiler::getInstance()->compileWithCallToStaticMethodAndContentFromArgumentName(
            $this,
            $argumentsName,
            $closureName,
            $this->resolveContentArgumentName(),
            ViewHelperCompiler::RENDER_STATIC,
            static::class
        );
        $initializationPhpCode .= $initialization;
        return $execution;
    }

    /**
     * @return string
     */
    protected function resolveContentArgumentName() {
        if (empty($this->contentArgumentName)) {
            $registeredArguments = call_user_func_array(array($this, 'prepareArguments'), array());
            foreach ($registeredArguments as $registeredArgument) {
                if (!$registeredArgument->isRequired()) {
                    $this->contentArgumentName = $registeredArgument->getName();
                    return $this->contentArgumentName;
                }
            }
            throw new Exception(
                'Attempting to compile %s failed. Chosen compile method requires that ViewHelper has ' .
                'at least one registered and optional argument'
            );
        }
        return $this->contentArgumentName;
    }

}