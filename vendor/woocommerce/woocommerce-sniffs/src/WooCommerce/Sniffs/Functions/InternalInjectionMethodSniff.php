<?php
/**
 * Sniff to require
 */

namespace WooCommerce\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class InternalInjectionMethodSniff implements Sniff
{

    /**
     * The name of the method that we're using for injection.
     *
     * @var string
     */
    public $injectionMethod = '';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * Processes this test when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr The position of the current token in the stack passed in $tokens.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // If we have no injection method defined we can't actually do anything.
        if (empty($this->injectionMethod)) {
            return;
        }

        // We only care to do this for the designated injection method.
        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($this->injectionMethod !== $methodName) {
            return;
        }

        // We're only interested in class methods.
        $classDefinition = $phpcsFile->getCondition($stackPtr, T_CLASS);
        if (false === $classDefinition) {
            return;
        }

        $methodProperties = $phpcsFile->getMethodProperties($stackPtr);
        if (!$methodProperties['is_final']) {
            $phpcsFile->addError('The injection method must be marked final', $stackPtr, 'MissingFinal');
        }
        if ('public' !== $methodProperties['scope']) {
            $phpcsFile->addError('The injection method must be marked public', $stackPtr, 'MissingPublic');
        }

        $this->checkAccessTag($phpcsFile, $stackPtr);
    }

    /**
     * Checks the see whether or not the current function has an appropriate access tag defined.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr The position of the current token in the stack passed in $tokens.
     */
    protected function checkAccessTag(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $find = Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;

        $foundInternalTag = false;

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);
        if (false !== $commentEnd) {
            if ($tokens[$commentEnd]['code'] === T_COMMENT) {
                // Inline comments might just be closing comments for
                // control structures or functions instead of function comments
                // using the wrong comment type. If there is other code on the line,
                // assume they relate to that code.
                $prev = $phpcsFile->findPrevious($find, ($commentEnd - 1), null, true);
                if ($prev !== false && $tokens[$prev]['line'] === $tokens[$commentEnd]['line']) {
                    $commentEnd = $prev;
                }
            }

            if ($tokens[$commentEnd]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
                $commentStart = $tokens[$commentEnd]['comment_opener'];
                foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
                    if ('@internal' === $tokens[$tag]['content']) {
                        $foundInternalTag = true;
                        break;
                    }
                }
            }
        }

        if (!$foundInternalTag) {
            $phpcsFile->addError(
                'The injection method requires an \'@internal\' annotation',
                $stackPtr,
                'MissingInternalTag'
            );
        }
    }
}
