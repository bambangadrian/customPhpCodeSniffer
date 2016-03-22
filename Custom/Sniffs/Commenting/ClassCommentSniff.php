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
if (class_exists('PEAR_Sniffs_Commenting_ClassCommentSniff', true) === false) {
    $error = 'Class PEAR_Sniffs_Commenting_ClassCommentSniff not found';
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
class Custom_Sniffs_Commenting_ClassCommentSniff extends PEAR_Sniffs_Commenting_ClassCommentSniff
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
            'order_text'     => 'precedes @package',
        ],
        '@package'    => [
            'required'       => true,
            'allow_multiple' => false,
            'order_text'     => 'follows @category',
        ],
        '@subpackage' => [
            'required'       => true,
            'allow_multiple' => false,
            'order_text'     => 'follows @package',
        ],
        '@author'     => [
            'required'       => true,
            'allow_multiple' => true,
            'order_text'     => 'follows @subpackage (if used) or @package',
        ],
        '@copyright'  => [
            'required'       => false,
            'allow_multiple' => true,
            'order_text'     => 'follows @author',
        ],
        '@license'    => [
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @copyright (if used) or @author',
        ],
        '@version'    => [
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @license',
        ],
        '@link'       => [
            'required'       => false,
            'allow_multiple' => true,
            'order_text'     => 'follows @version',
        ],
        '@see'        => [
            'required'       => false,
            'allow_multiple' => true,
            'order_text'     => 'follows @link',
        ],
        '@since'      => [
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @see (if used) or @link',
        ],
        '@deprecated' => [
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @since (if used) or @see (if used) or @link',
        ],
    ];

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
            //$phpcsFile->addError($error, $tag, 'InvalidPackage', $data);
        }//end foreach
    }//end processPackage()

    /**
     * Process the subpackage tag.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param array                $tags      The tokens for these tags.
     *
     * @return void
     */
    protected function processSubpackage(PHP_CodeSniffer_File $phpcsFile, array $tags)
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
            $nameBits = explode('_', $newContent);
            $firstBit = array_shift($nameBits);
            $newName = strtoupper($firstBit{0}) . substr($firstBit, 1) . '_';
            foreach ($nameBits as $bit) {
                if ($bit !== '') {
                    $newName .= strtoupper($bit{0}) . substr($bit, 1) . '_';
                }
            }
            $error = 'Subpackage name "%s" is not valid; consider "%s" instead';
            $validName = trim($newName, '_');
            $data = [
                $content,
                $validName,
            ];
            // $phpcsFile->addError($error, $tag, 'InvalidSubpackage', $data);
        }//end foreach
    }//end processSubpackage()
}
