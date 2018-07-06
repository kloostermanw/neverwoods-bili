<?php

namespace Bili;

/**
 * Class to hold Sanitize logic.
 *
 * @package Bili
 */
class Sanitize
{
    public static function toXhtml($strOutput)
    {
        $strReturn = $strOutput;

        //*** Replace & characters with &amp;.
        self::filterAmpersandEntity($strReturn);

        //*** Replace $ characters with &#36;.
        self::filterDollarEntity($strReturn);

        //*** Replace BAD link targets with GOOD rels.
        self::filterXhtmlLinkTarget($strReturn);

        return $strReturn;
    }

    /**
     * Convert all special characters in a string or array to HTML Entities.
     *
     * @param string|array $varValue
     * @return string|array
     */
    public static function toEntities($varValue)
    {
        if (is_array($varValue)) {
            $varReturn = [];
            foreach ($varValue as $key => $value) {
                $varReturn[$key] = htmlentities($value, ENT_QUOTES | ENT_IGNORE, 'UTF-8', false);
            }
        } else {
            $varReturn = htmlentities($varValue, ENT_QUOTES | ENT_IGNORE, 'UTF-8', false);
        }

        return $varReturn;
    }

    /**
     * Convert all HTML Entities in a string to special characters.
     *
     * @param string|array $varValue
     * @return string|array
     */
    public static function fromEntities($varValue)
    {
        if (is_array($varValue)) {
            $varReturn = [];
            foreach ($varValue as $key => $value) {
                $varReturn[$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            }
        } else {
            $varReturn = html_entity_decode($varValue, ENT_QUOTES, 'UTF-8');
        }

        return $varReturn;
    }

    public static function toXml($strOutput)
    {
        $strReturn = $strOutput;

        //*** Replace & characters with &amp;.
        self::filterAmpersandEntity($strReturn);

        //*** Replace $ characters with &#36;.
        self::filterDollarEntity($strReturn);

        return $strReturn;
    }

    public static function toFilename($strOutput)
    {
        $strOutput = preg_replace('/([^\w\s\d\-\.%_~,;:\(\)\[\]\|])/u', '', $strOutput);

        return $strOutput;
    }

    /**
     * Convert any numeric input to a machine readable decimal.
     *
     * Possible input:
     * 1.541.045,45
     * 1,541,045.45
     * 1541045,45
     * 1541045.45
     *
     * Output:
     * 1541045.45
     *
     * @param  mixed   $varInput           Either number or string that needs conversion
     * @param  boolean $blnForceConversion Indicate if the input has to be converted in any case.
     *                                     Strings return 0 if true.
     * @return mixed   Either the converted value of the original value if conversion wasn't forced
     */
    public static function toDecimal($varInput, $blnForceConversion = true)
    {
        $varReturn = 0;

        if (strpos($varInput, ".") < strpos($varInput, ",")) {
            $varInput = str_replace(".", "", $varInput);
            $varInput = strtr($varInput, ",", ".");
        } else {
            $varInput = str_replace(",", "", $varInput);
        }

        $varReturn = (float) $varInput;

        // If the conversion isn't forced we check for specific cases.
        if (!$blnForceConversion) {
            //*** If the return value is 0 and the input was longer we return the input value.
            if ($varReturn === 0.0 && strlen($varInput) > 1) {
                $varReturn = $varInput;
            }

            //*** If the return value has an exponent in it we return the input value.
            if (stristr((string) $varReturn, "e+") !== false) {
                $varReturn = $varInput;
            }
        }

        return $varReturn;
    }

    public static function br2nl($strInput)
    {
        $strReturn = str_replace("<br>", "\n", $strInput);
        $strReturn = str_replace("<br/>", "\n", $strReturn);
        $strReturn = str_replace("<br />", "\n", $strReturn);

        return $strReturn;
    }

    /**
     * Sanitize input to be an integer. Works on single values and arrays.
     *
     * @param  string|decimal|array $varInput
     * @param  boolean $blnDiscardInvalid Indicate if the input array should be compacted, leaving out invalid values.
     * @return Ambigous <NULL, number, multitype:number >
     */
    public static function toInteger($varInput, $blnDiscardInvalid = true)
    {
        $varReturn = null;

        if (is_array($varInput)) {
            $varReturn = array();
            foreach ($varInput as $key => $value) {
                if ($blnDiscardInvalid) {
                    if (is_numeric($value) || (int) $value > 0) {
                        $varReturn[] = (int) $value;
                    }
                } else {
                    $varReturn[$key] = (int) $value;
                }
            }
        } else {
            $varReturn = (int) $varInput;
        }

        return $varReturn;
    }

    /**
     * Convert an input string to a save URL, which means no special characters, spaces or uppercase.
     * Spaces get converted to hyphens.
     *
     * @param $strInput
     * @return string
     */
    public static function toUrl($strInput)
    {
        //*** Convert HTML entities to utf-8 characters.
        $strReturn = html_entity_decode($strInput, ENT_QUOTES | ENT_XML1, 'UTF-8');

        //*** Convert utf-8 to ascii characters.
        $strReturn = iconv('UTF-8', 'ASCII//TRANSLIT', $strReturn);

        //*** Convert to lower case, trim and replace spaces with dashes.
        $strReturn = str_replace(' ', '-', trim(strtolower($strReturn)));

        //*** Remove anything that isn't a regular character or number.
        $strReturn = preg_replace('/[^\w\s\d\-]/', '', $strReturn);

        //*** Make sure the is only 1 hyphen. Could be more due to space replacement and special character removal.
        $strReturn = preg_replace('/-{2,}/', '-', $strReturn);

        return $strReturn;
    }

    /**
     * Sanitize input to be a numeric value. Works on single values and arrays.
     * This will retain leading zeros.
     *
     * @param  string|decimal|array $varInput
     * @param  boolean $blnDiscardInvalid Indicate if the input array should be compacted, leaving out invalid values.
     * @return Ambigous <NULL, number, multitype:number >
     */
    public static function toNumeric($varInput, $blnDiscardInvalid = true)
    {
        $varReturn = null;

        if (is_array($varInput)) {
            $varReturn = array();
            foreach ($varInput as $key => $value) {
                if ($blnDiscardInvalid) {
                    if (is_numeric($value) || (int) $value > 0) {
                        $varReturn[] = $value;
                    }
                } else {
                    $varReturn[$key] = (is_numeric($value)) ? $value : (int) $value;
                }
            }
        } else {
            $varReturn = (is_numeric($varInput)) ? $varInput : (int) $varInput;
        }

        return $varReturn;
    }

    /**
     * Sanitize a value to string using the filter_var method and constants.
     *
     * @param string $varInput
     * @return string|bool
     */
    public static function toString($varInput)
    {
        $strReturn = filter_var(trim($varInput), FILTER_SANITIZE_STRING);

        return $strReturn;
    }

    /**
     * Sanitize a string to a pure ascii string. No special characters or any other fancy UTF-8 stuff.
     *
     * @param string $strInput
     * @return string
     */
    public static function toAscii($strInput)
    {
        $strReturn = $strInput;

        $strNew = iconv("utf-8", "ascii//TRANSLIT", $strInput);
        if ($strNew !== false) {
            $strReturn = $strNew;
        }

        return $strReturn;
    }

    private static function filterAmpersandEntity(&$text)
    {
        $text = preg_replace('/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w{1,8});)/i', "&amp;", $text);
    }

    private static function filterDollarEntity(&$text)
    {
        $text = str_replace("$", "&#36;", $text);
    }

    private static function filterXhtmlLinkTarget(&$text)
    {
        $text = str_ireplace("target=\"_blank\"", "rel=\"external\"", $text);
        $text = str_ireplace("target=\"_top\"", "rel=\"external\"", $text);
    }
}
