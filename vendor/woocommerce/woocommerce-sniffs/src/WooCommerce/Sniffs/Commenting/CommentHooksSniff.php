<?php
/**
 * Sniff to ensure hooks have doc comments.
 */

namespace WooCommerce\Sniffs\Commenting;

use PHP_CodeSniffer\Util\Tokens;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Comment tags sniff.
 */
class CommentHooksSniff implements Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [
        'PHP',
    ];

    /**
     * A list of specfic hooks to listen to.
     *
     * @var array
     */
    public $hooks = [
        'do_action',
        'apply_filters',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register(): array
    {
        return [T_STRING];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (! in_array($tokens[$stackPtr]['content'], $this->hooks)) {
            return;
        }

        $previous_comment = $phpcsFile->findPrevious(Tokens::$commentTokens, ( $stackPtr - 1 ));

        if (false !== $previous_comment) {
            $correctly_placed = false;

            if (( $tokens[ $previous_comment ]['line'] + 1 ) === $tokens[ $stackPtr ]['line']) {
                $correctly_placed = true;
            }

            if (true === $correctly_placed) {

                if (\T_COMMENT === $tokens[ $previous_comment ]['code']) {
                    $phpcsFile->addError(
                        'A "hook" comment must be a "/**" style docblock comment.',
                        $stackPtr,
                        'HookCommentWrongStyle'
                    );

                    return;
                } elseif (\T_DOC_COMMENT_CLOSE_TAG === $tokens[ $previous_comment ]['code']) {
                    $comment_start = $phpcsFile->findPrevious(\T_DOC_COMMENT_OPEN_TAG, ( $previous_comment - 1 ));

                    // Iterate through each comment to check for "@since" tag.
                    foreach ($tokens[ $comment_start ]['comment_tags'] as $tag) {
                        if ($tokens[$tag]['content'] === '@since') {
                            return;
                        }
                    }

                    $phpcsFile->addError(
                        'Docblock comment was found for the hook but does not contain a "@since" versioning.',
                        $stackPtr,
                        'MissingSinceComment'
                    );

                    return;
                }
            }
        }

        // Found hook but no docblock comment.
        $phpcsFile->addError(
            'A hook was found, but was not accompanied by a docblock comment on the line above to clarify the meaning of the hook.',
            $stackPtr,
            'MissingHookComment'
        );

        return;
    }
}
