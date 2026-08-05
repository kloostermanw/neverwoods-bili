<?php

namespace Bili;

/**
 * Language Selection Class v0.2.0
 * Used to get the default language, set a specific language and list
 * available languages.
 *
 * CHANGELOG
 * version 0.2.0, 16 Dec 2015
 *   NEW: Improve the overwrite logic with multiple paths
 * version 0.1.2, 26 Mar 2013
 *   NEW: Show translation key and category
 * version 0.1.1, 19 Mar 2013
 *   FIX: Made the $_LANG variable global.
 * version 0.1.0, 03 Apr 2006
 *   NEW: Created class.
 */

class Language
{
    /** @var Language|null */
    private static $instance		= null;
    /** @var string|null */
    protected static $sanitizeType    = null;
    /** @var bool */
    protected static $secureCookie    = false;
    /** @var array<string, array<string, string>> */
    private static $languages       = array();
    /** @var string */
    private static $error           = "TRANSLATION '%s' NOT FOUND IN '%s'.";
    /** @var string */
    public $name                    = "";
    /** @var string */
    public $language                = "";
    /** @var string */
    private $defaultLang            = "";
    /** @var string */
    private $langPath               = "";
    /** @var string[]|null */
    private $langOverwritePaths     = null;
    /** @var string */
    private $activeLang             = "";
    /** @var bool */
    private $forceReload            = false;

    /**
     * Private: instances are created by singleton() or getInstance().
     * Resolves and loads the language file immediately.
     *
     * @param string $strLang
     * @param string $langPath
     * @param string|string[]|null $overwritePaths
     */
    private function __construct($strLang = "", $langPath = "", $overwritePaths = null)
    {
        $this->defaultLang = $strLang;
        $this->langPath = $langPath;

        if (!is_null($overwritePaths)) {
            $this->setOverwritePath($overwritePaths);
        }

        $this->getLang();
    }

    /**
     * @param string $strLang
     * @param string $langPath
     * @param string|string[]|null $varOverwritePaths
     * @return Language
     */
    public static function singleton($strLang = "english-utf-8", $langPath = "./lng/", $varOverwritePaths = null)
    {
        self::$instance = new Language($strLang, $langPath, $varOverwritePaths);

        return self::$instance;
    }

    /**
     *
     * @return Language An instance of Language
     */
    public static function getInstance()
    {
        /* Get the singleton instance for this class */

        if (is_null(self::$instance)) {
            self::$instance = new Language();
        }

        return self::$instance;
    }

    /**
     * @param string|null $strSanitizeType
     * @return void
     */
    public static function setSanitize($strSanitizeType)
    {
        static::$sanitizeType = $strSanitizeType;
    }

    /**
     * @param bool $blnValue
     * @return void
     */
    public static function setUseSecureCookie($blnValue)
    {
        static::$secureCookie = $blnValue;
    }

    /**
     * @return string
     */
    public function getActiveLang()
    {
        return $this->activeLang;
    }

    /**
     * @param string $strName
     * @param string $strCategory
     * @param bool $blnReturnError
     * @return string|array<string>
     */
    public static function get($strName, $strCategory = "global", $blnReturnError = true)
    {
        //*** Get a translation from the language file.
        $strReturn = ($blnReturnError) ? sprintf(self::$error, $strName, $strCategory) : "";

        if (isset(self::$languages[$strCategory][$strName])) {
            $strReturn = self::$languages[$strCategory][$strName];
        }

        //*** Output sanitization?
        switch (self::$sanitizeType) {
            case "xhtml":
                if (class_exists("Bili\\Sanitize", true)) {
                    $strReturn = Sanitize::toXhtml($strReturn);
                }

                break;
            case "entities":
                if (class_exists("Bili\\Sanitize", true)) {
                    $strReturn = Sanitize::toEntities($strReturn);
                }

                break;
        }

        return $strReturn;
    }

    /**
     * @param string $strLang
     * @return bool
     */
    public function getLang($strLang = "")
    {
        /* Get a specific language or, if argument is empty, get the
         * language by checking session, cookie and default.
         */
        $blnReturn = false;

        if (empty($strLang)) {
            if (!empty($_SESSION['language']) && file_exists($this->langPath . "/" . $_SESSION['language'] . ".php")) {
                //*** Session variable exists. Load the language file.
                $this->activeLang = $_SESSION['language'];
            } elseif (!empty($_COOKIE['language'])
                    && file_exists($this->langPath . "/" . $_COOKIE['language'] . ".php")) {
                //*** Cookie variable exists. Load the language file.
                $this->activeLang = $_COOKIE['language'];
            } elseif (file_exists($this->langPath . "/" . $this->defaultLang . ".php")) {
                //*** Load default language file.
                $this->activeLang = $this->defaultLang;
            }
        } elseif (file_exists($this->langPath . "/" . $strLang . ".php")) {
            //*** Load the specific language file.
            $this->activeLang = $strLang;
        }

        //*** Really load the file.
        if (!empty($this->activeLang)) {
            require($this->langPath . "/" . $this->activeLang . ".php");

            if (is_array($this->langOverwritePaths)) {
                foreach ($this->langOverwritePaths as $strPath) {
                    if (file_exists($strPath . "/" . $this->activeLang . ".php")) {
                        require($strPath . "/" . $this->activeLang . ".php");
                    }
                }
            }

            //*** Check if the expected variable exists.
            (isset($_LANG)) ? self::$languages = $_LANG : self::$languages = array();

            //*** Set internal variables.
            $this->name = $this->activeLang;

            $arrTemp = explode("-", $this->activeLang);
            $this->language = str_replace("_", " ", $arrTemp[0]);

            $this->setLocale();

            $blnReturn = true;
        }

        return $blnReturn;
    }

    /**
     * Sets a specific language and loads its file, session and cookie included.
     * Pass $blnPersist = false to load without writing either.
     * Persists only when the file is loaded.
     *
     * @param string $strLang
     * @param bool $blnPersist
     * @return bool True when the language file was loaded, false when nothing changed.
     */
    public function setLang($strLang, $blnPersist = true)
    {
        $blnReturn = false;

        if ($strLang !== $this->activeLang) {
            $this->forceReload = true;
        }

        //*** Check if the language file exists and is different from the current language.
        if (file_exists($this->langPath . "/" . $strLang . ".php")
                && (count(self::$languages) === 0 || $this->forceReload)) {
            if ($blnPersist) {
                //*** Persist the choice to the cookie and the session.
                $this->writeCookie($strLang);
                $_SESSION['language'] = $strLang;
            }

            //*** Load new language file;
            $this->getLang($strLang);
            $blnReturn = true;
        }

        return $blnReturn;
    }

    /**
     * Writes the 30-day language cookie, ignoring failures.
     * A missing cookie falls back to the default language.
     *
     * @param string $strLang
     * @return void
     */
    private function writeCookie($strLang)
    {
        try {
            setcookie(
                'language',
                $strLang,
                time() + 60*60*24*30,
                '/',
                '',
                static::$secureCookie,
                true
            );
        } catch (\Exception $ex) {
            //*** Only thrown when the host converts warnings to exceptions; plain
            //*** PHP warns and returns false. The cookie is not worth failing over.
        }
    }

    /**
     * @return LanguageCollection
     */
    public function getLangs()
    {
        $objReturn = new LanguageCollection();

        //*** List all files in the language directory.
        if (is_dir($this->langPath)) {
            $dirHandle = opendir($this->langPath);
            if ($dirHandle) {
                while (($objFile = readdir($dirHandle)) !== false) {
                    if (is_file($this->langPath . $objFile)) {
                        //*** Create Language_File object and set properties,
                        $objLanguage = new LanguageFile();
                        $objLanguage->name = basename($objFile, ".php");

                        $arrTemp = explode("-", $objFile);
                        $objLanguage->language = str_replace("_", " ", $arrTemp[0]);

                        //*** Add to the collection.
                        $objReturn->addObject($objLanguage);
                    }
                }
                closedir($dirHandle);
            }
        }

        return $objReturn;
    }

    /**
     * @return string|false
     */
    public function setLocale()
    {
        $varReturn = false;

        $strLocale = $this->get("locale");
        $varReturn = setlocale(LC_ALL, $strLocale);

        return $varReturn;
    }

    /**
     * Set a path or array of paths that shoud be included in order after the initial language file.
     * This will override specific items in the language array.
     *
     * @param string|string[] $varPath
     * @return void
     */
    public function setOverwritePath($varPath)
    {
        //*** Sanitize the input value.
        $arrPaths = (!is_array($varPath)) ? [$varPath] : $varPath;

        //*** Check the paths for existence and add to temp. array.
        $arrNewPaths = [];
        foreach ($arrPaths as $strPath) {
            if (file_exists($strPath)) {
                $arrNewPaths[] = $strPath;
            }
        }

        if ($arrNewPaths !== $this->langOverwritePaths) {
            $this->langOverwritePaths = $arrNewPaths;

            $this->forceReload = true;
        }
    }
}
