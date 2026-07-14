<?php

namespace Bili\Tests;

use Bili\Language;
use Bili\LanguageCollection;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    public const EN = "english-utf-8";
    public const NL = "nederlands-utf-8";

    /**
     * Tests getInstance.
     *
     * @return void
     */
    public function testGetInstance(): void
    {
        $objLanguage = Language::getInstance();
        $this->assertInstanceOf(Language::class, $objLanguage);
    }

    /**
     * Tests set lang.
     *
     * @return void
     */
    public function testSetLangNL(): void
    {
        $objLanguage = Language::getInstance();
        $objLanguage->setLang(self::NL);
        $this->assertEquals(self::NL, $objLanguage->getActiveLang());
    }

    /**
     * Tests set lang.
     *
     * @return void
     */
    public function testSetLangEN(): void
    {
        $objLanguage = Language::getInstance();
        $objLanguage->setLang(self::EN);
        $this->assertEquals(self::EN, $objLanguage->getActiveLang());
    }

    /**
     * Tests get active language.
     *
     * @return void
     */
    public function testGetActiveLang(): void
    {
        $objLanguage = Language::getInstance();
        $this->assertEquals(self::EN, $objLanguage->getActiveLang());
    }

    /**
     * Tests get translation in English.
     *
     * @return void
     */
    public function testGetEn(): void
    {
        Language::getInstance()->setLang(self::EN);
        $this->assertEquals("en", Language::get('abbr'));
        $this->assertEquals("Yes", Language::get('yes', 'message'));
    }

    /**
     * Tests get translation in Dutch.
     *
     * @return void
     */
    public function testGetNl(): void
    {
        Language::getInstance()->setLang(self::NL);
        $this->assertEquals("nl", Language::get('abbr'));
        $this->assertEquals("Ja", Language::get('yes', 'message'));
    }

    /**
     * Tests get translation in Dutch.
     *
     * @return void
     */
    public function testGetLangs(): void
    {
        $this->assertInstanceOf(LanguageCollection::class, Language::getInstance()->getLangs());

    }

    /**
     * setLang() with $blnPersist = false loads the language in-process but must
     * not write $_SESSION['language'] (stateless API use case, bili#29).
     *
     * @return void
     */
    public function testSetLangNoPersistDoesNotWriteSession(): void
    {
        // Normalize to a known active language, then clear the session so we
        // only observe writes caused by the switch under test.
        Language::getInstance()->setLang(self::EN);
        $_SESSION = [];

        $objLanguage = Language::getInstance();
        $objLanguage->setLang(self::NL, false);

        $this->assertEquals(self::NL, $objLanguage->getActiveLang());
        $this->assertArrayNotHasKey('language', $_SESSION);
    }

    /**
     * setLang() with $blnPersist = false must still load the language file so
     * subsequent Language::get() calls return strings in that language.
     *
     * @return void
     */
    public function testSetLangNoPersistStillLoadsLanguageFile(): void
    {
        Language::getInstance()->setLang(self::EN);
        Language::getInstance()->setLang(self::NL, false);

        $this->assertEquals('nl', Language::get('abbr'));
        $this->assertEquals('Ja', Language::get('yes', 'message'));
    }

    /**
     * The default ($blnPersist = true) preserves today's behavior: the chosen
     * language is written to $_SESSION['language'].
     *
     * @return void
     */
    public function testSetLangPersistWritesSession(): void
    {
        Language::getInstance()->setLang(self::EN);
        $_SESSION = [];
        Language::getInstance()->setLang(self::NL);

        $this->assertEquals(self::NL, $_SESSION['language']);
    }

    /**
     * The default ($blnPersist = true) still writes the language cookie.
     *
     * @return void
     */
    public function testSetLangPersistWritesCookie(): void
    {
        $_SESSION = [];
        $spy = $this->createCookieSpy();
        $spy->setLang(self::NL);

        $this->assertSame(1, $spy->cookieWrites);
    }

    /**
     * setLang() with $blnPersist = false must not write the language cookie.
     *
     * @return void
     */
    public function testSetLangNoPersistDoesNotWriteCookie(): void
    {
        $_SESSION = [];
        $spy = $this->createCookieSpy();
        $spy->setLang(self::NL, false);

        $this->assertSame(0, $spy->cookieWrites);
    }

    /**
     * A Language subclass that records cookie writes instead of sending a real
     * Set-Cookie header, which is unobservable under the CLI SAPI.
     *
     * @return Language
     */
    private function createCookieSpy()
    {
        return new class extends Language {
            /** @var int */
            public $cookieWrites = 0;

            public function __construct()
            {
                parent::__construct("english-utf-8", __DIR__ . '/languages/');
            }

            protected function writeCookie($strLang)
            {
                $this->cookieWrites++;
            }
        };
    }

    public function setUp(): void
    {
        parent::setUp();
        setlocale(LC_ALL, 'en_US.UTF-8');
        $objLanguage = Language::singleton("english-utf-8", __DIR__ . '/languages/');
        $objLanguage->setLocale();
    }
}