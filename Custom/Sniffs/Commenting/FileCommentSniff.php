<?php
/**
 * Parses and verifies the doc comments for classes.
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: ClassCommentSniff.php 301632 2010-07-28 01:57:56Z squiz $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
if (class_exists('PHP_CodeSniffer_Tokenizers_Comment', true) === false) {
    $error = 'Class PHP_CodeSniffer_Tokenizers_Comment not found';
    throw new PHP_CodeSniffer_Exception($error);
}
if (class_exists('PEAR_Sniffs_Commenting_FileCommentSniff', true) === false) {
    $error = 'Class PEAR_Sniffs_Commenting_FileCommentSniff not found';
    throw new PHP_CodeSniffer_Exception($error);
}

/**
 * Parses and verifies the doc comments for classes.
 * Verifies that :
 * <ul>
 *  <li>A doc comment exists.</li>
 *  <li>There is a blank newline after the short description.</li>
 *  <li>There is a blank newline between the long and short description.</li>
 *  <li>There is a blank newline between the long description and tags.</li>
 *  <li>Check the order of the tags.</li>
 *  <li>Check the indentation of each tag.</li>
 *  <li>Check required and optional tags and the format of their content.</li>
 * </ul>
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.3.0RC2
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Custom_Sniffs_Commenting_FileCommentSniff extends PEAR_Sniffs_Commenting_FileCommentSniff
{

    /**
     * Tags in correct order and related info.
     *
     * @var array
     */
    protected $tags = [
        '@category'   => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@package'    => [
            'required'       => true,
            'allow_multiple' => false,
        ],
        '@subpackage' => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@author'     => [
            'required'       => true,
            'allow_multiple' => true,
        ],
        '@copyright'  => [
            'required'       => true,
            'allow_multiple' => true,
        ],
        '@license'    => [
            'required'       => true,
            'allow_multiple' => false,
        ],
        '@version'    => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@link'       => [
            'required'       => true,
            'allow_multiple' => true,
        ],
        '@see'        => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@since'      => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@deprecated' => [
            'required'       => false,
            'allow_multiple' => false,
        ],
    ];

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return int
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        // Find the next non whitespace token.
        $commentStart = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        // Allow declare() statements at the top of the file.
        if ($tokens[$commentStart]['code'] === T_DECLARE) {
            $semicolon = $phpcsFile->findNext(T_SEMICOLON, ($commentStart + 1));
            $commentStart = $phpcsFile->findNext(T_WHITESPACE, ($semicolon + 1), null, true);
        }
        // Ignore vim header.
        if ($tokens[$commentStart]['code'] === T_COMMENT) {
            if (strstr($tokens[$commentStart]['content'], 'vim:') !== false) {
                $commentStart = $phpcsFile->findNext(
                    T_WHITESPACE,
                    ($commentStart + 1),
                    null,
                    true
                );
            }
        }
        $errorToken = ($stackPtr + 1);
        if (isset($tokens[$errorToken]) === false) {
            $errorToken--;
        }
        if ($tokens[$commentStart]['code'] === T_CLOSE_TAG) {
            // We are only interested if this is the first open tag.
            return ($phpcsFile->numTokens + 1);
        } else {
            if ($tokens[$commentStart]['code'] === T_COMMENT) {
                $error = 'You must use "/**" style comments for a file comment';
                $phpcsFile->addError($error, $errorToken, 'WrongStyle');
                $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'yes');
                return ($phpcsFile->numTokens + 1);
            } else {
                if ($commentStart === false
                    || $tokens[$commentStart]['code'] !== T_DOC_COMMENT_OPEN_TAG
                ) {
                    $phpcsFile->addError('Missing file doc comment', $errorToken, 'Missing');
                    $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'no');
                    return ($phpcsFile->numTokens + 1);
                } else {
                    $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'yes');
                }
            }
        }
        // Check the PHP Version, which should be in some text before the first tag.
        $commentEnd = $tokens[$commentStart]['comment_closer'];
        $found = false;
        for ($i = ($commentStart + 1); $i < $commentEnd; $i++) {
            if ($tokens[$i]['code'] === T_DOC_COMMENT_TAG) {
                break;
            } else {
                if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING
                    && strstr(strtolower($tokens[$i]['content']), 'php version') !== false
                ) {
                    $found = true;
                    break;
                }
            }
        }
        if ($found === false) {
            //   $error = 'PHP version not specified';
            //   $phpcsFile->addWarning($error, $commentEnd, 'MissingVersion');
        }
        // Check each tag.
        $this->processTags($phpcsFile, $stackPtr, $commentStart);
        // Ignore the rest of the file.
        return ($phpcsFile->numTokens + 1);
    }//end process()

    /**
     * Process the package tag.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param array                $tags      The tokens for these tags.
     *
     * @return void
     */
    protected function processPackage(PHP_CodeSniffer_File $phpcsFile, array $tags)
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($tags as $tag) {
            if ($tokens[($tag + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                // No content.
                continue;
            }
            $content = $tokens[($tag + 2)]['content'];
            if (PHP_CodeSniffer::isUnderscoreName($content) === true) {
                continue;
            }
            $newContent = str_replace(' ', '_', $content);
            $newContent = trim($newContent, '_');
            $newContent = preg_replace('/[^A-Za-z_]/', '', $newContent);
            $nameBits = explode('_', $newContent);
            $firstBit = array_shift($nameBits);
            $newName = strtoupper($firstBit{0}) . substr($firstBit, 1) . '_';
            foreach ($nameBits as $bit) {
                if ($bit !== '') {
                    $newName .= strtoupper($bit{0}) . substr($bit, 1) . '_';
                }
            }
            $error = 'Package name "%s" is not valid; consider "%s" instead';
            $validName = trim($newName, '_');
            $data = [
                $content,
                $validName,
            ];
            // $phpcsFile->addError($error, $tag, 'InvalidPackage', $data);
        }//end foreach
    }//end processPackage()
}
