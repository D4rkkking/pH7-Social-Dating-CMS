<?php
/**
 * @title          Search User Core Form
 *
 * @author         Pierre-Henry Soria <ph7software@gmail.com>
 * @copyright      (c) 2012-2018, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package        PH7 / App / System / Core / Form
 */

namespace PH7;

use Couchbase\SearchQuery;
use PH7\Framework\Geo\Ip\Geo;
use PH7\Framework\Mvc\Model\DbConfig;
use PH7\Framework\Mvc\Request\Http as HttpRequest;
use PH7\Framework\Mvc\Router\Uri;
use PH7\Framework\Session\Session;

class SearchUserCoreForm
{
    /**
     * Default field attributes.
     */
    private static $aSexOption = ['required' => 1];
    private static $aMatchSexOption = ['required' => 1];
    private static $aCountryOption = ['id' => 'str_country'];
    private static $aCityOption = ['id' => 'str_city'];
    private static $aStateOption = ['id' => 'str_state'];
    private static $aAgeOption;
    private static $aLatestOrder = [];
    private static $aAvatarOnly = [];
    private static $aOnlineOnly = [];

    /**
     * @param integer $iWidth Width of the form in pixel. If null, will be 100%
     * @param boolean $bSetDefVals Set default values in the form fields, or not...
     *
     * @return void HTML output.
     */
    public static function quick($iWidth = null, $bSetDefVals = true)
    {
        if ($bSetDefVals) {
            self::setAttrVals();
        }

        // Generate the Quick Search form
        $oForm = new \PFBC\Form('form_search', $iWidth);
        $oForm->configure(['action' => Uri::get('user', 'browse', 'index') . PH7_SH, 'method' => 'get']);
        $oForm->addElement(new \PFBC\Element\Hidden('submit_search', 'form_search'));
        $oForm->addElement(
            new \PFBC\Element\Select(
                t('I am a:'),
                'match_sex',
                [
                    GenderTypeUserCore::MALE => t('Man'),
                    GenderTypeUserCore::FEMALE => t('Woman'),
                    GenderTypeUserCore::COUPLE => t('Couple')
                ],
                self::$aSexOption
            )
        );
        $oForm->addElement(
            new \PFBC\Element\Checkbox(
                t('Looking for a:'),
                'sex',
                [
                    GenderTypeUserCore::FEMALE => t('Woman'),
                    GenderTypeUserCore::MALE => t('Man'),
                    GenderTypeUserCore::COUPLE => t('Couple')
                ],
                self::$aMatchSexOption
            )
        );
        $oForm->addElement(new \PFBC\Element\Age(self::$aAgeOption));
        $oForm->addElement(new \PFBC\Element\Select(t('Country:'), SearchQueryCore::COUNTRY, Form::getCountryValues(), self::$aCountryOption));
        $oForm->addElement(new \PFBC\Element\Textbox(t('City:'), SearchQueryCore::CITY, self::$aCityOption));
        $oForm->addElement(new \PFBC\Element\Checkbox('', SearchQueryCore::ORDER, [SearchCoreModel::LATEST => '<span class="bold">' . t('Latest members') . '</span>'], self::$aLatestOrder));
        $oForm->addElement(new \PFBC\Element\Checkbox('', SearchQueryCore::AVATAR, ['1' => '<span class="bold">' . t('Only with Avatar') . '</span>'], self::$aAvatarOnly));
        $oForm->addElement(new \PFBC\Element\Checkbox('', SearchQueryCore::ONLINE, ['1' => '<span class="bold green2">' . t('Only Online') . '</span>'], self::$aOnlineOnly));
        $oForm->addElement(new \PFBC\Element\Button(t('Search'), 'submit', ['icon' => 'search']));
        $oForm->addElement(new \PFBC\Element\HTMLExternal('<script src="' . PH7_URL_STATIC . PH7_JS . 'geo/autocompleteCity.js"></script>'));
        $oForm->render();
    }

    /**
     * @param integer $iWidth Width of the form in pixel. If null, will be 100%
     * @param boolean $bSetDefVals Set default values in the form fields, or not...
     *
     * @return void HTML output.
     */
    public static function advanced($iWidth = null, $bSetDefVals = true)
    {
        if ($bSetDefVals) {
            self::setAttrVals();
        }

        // Generate the Advanced Search form
        $oForm = new \PFBC\Form('form_search', $iWidth);
        $oForm->configure(['action' => Uri::get('user', 'browse', 'index') . PH7_SH, 'method' => 'get']);
        $oForm->addElement(new \PFBC\Element\Hidden('submit_search', 'form_search'));
        $oForm->addElement(
            new \PFBC\Element\Select(
                t('I am a:'),
                'match_sex',
                [
                    GenderTypeUserCore::MALE => t('Male'),
                    GenderTypeUserCore::FEMALE => t('Woman'),
                    GenderTypeUserCore::COUPLE => t('Couple')
                ],
                self::$aSexOption
            )
        );
        $oForm->addElement(
            new \PFBC\Element\Checkbox(
                t('Looking for:'),
                'sex',
                [
                    GenderTypeUserCore::FEMALE => t('Woman'),
                    GenderTypeUserCore::MALE => t('Male'),
                    GenderTypeUserCore::COUPLE => t('Couple')
                ],
                self::$aMatchSexOption
            )
        );
        $oForm->addElement(new \PFBC\Element\Age(self::$aAgeOption));
        $oForm->addElement(new \PFBC\Element\Select(t('Country:'), SearchQueryCore::COUNTRY, Form::getCountryValues(), self::$aCountryOption));
        $oForm->addElement(new \PFBC\Element\Textbox(t('City:'), SearchQueryCore::CITY, self::$aCityOption));
        $oForm->addElement(new \PFBC\Element\Textbox(t('State/Province:'), SearchQueryCore::STATE, self::$aStateOption));
        $oForm->addElement(new \PFBC\Element\Textbox(t('Postal Code:'), SearchQueryCore::ZIP_CODE, ['id' => 'str_zip_code']));
        $oForm->addElement(new \PFBC\Element\Email(t('Email Address:'), SearchQueryCore::EMAIL));
        $oForm->addElement(new \PFBC\Element\Checkbox('', SearchQueryCore::AVATAR, ['1' => '<span class="bold">' . t('Only with Avatar') . '</span>']));
        $oForm->addElement(new \PFBC\Element\Checkbox('', SearchQueryCore::ONLINE, ['1' => '<span class="bold green2">' . t('Only Online') . '</span>']));
        $oForm->addElement(
            new \PFBC\Element\Select(
                t('Browse By:'),
                SearchQueryCore::ORDER,
                [
                    SearchCoreModel::LATEST => t('Latest Members'),
                    SearchCoreModel::LAST_ACTIVITY => t('Last Activity'),
                    SearchCoreModel::VIEWS => t('Most Popular'),
                    SearchCoreModel::RATING => t('Top Rated'),
                    SearchCoreModel::USERNAME => t('Username'),
                    SearchCoreModel::FIRST_NAME => t('First Name'),
                    SearchCoreModel::LAST_NAME => t('Last Name'),
                    SearchCoreModel::EMAIL => t('Email')
                ]
            )
        );
        $oForm->addElement(
            new \PFBC\Element\Select(
                t('Direction:'),
                SearchQueryCore::SORT,
                [
                    SearchCoreModel::DESC => t('Descending'),
                    SearchCoreModel::ASC => t('Ascending')
                ]
            )
        );
        $oForm->addElement(new \PFBC\Element\Button(t('Search'), 'submit', ['icon' => 'search']));
        $oForm->addElement(new \PFBC\Element\HTMLExternal('<script src="' . PH7_URL_STATIC . PH7_JS . 'geo/autocompleteCity.js"></script>'));
        $oForm->render();
    }

    /**
     * If a user is logged, get the relative 'user_sex' and 'match_sex' for better and more intuitive search.
     *
     * @param UserCoreModel $oUserModel
     * @param Session $oSession
     *
     * @return array The 'user_sex' and 'match_sex'
     */
    protected static function getGenderVals(UserCoreModel $oUserModel, Session $oSession)
    {
        $sUserSex = 'male';
        $aMatchSex = ['male', 'female', 'couple'];

        if (UserCore::auth()) {
            $sUserSex = $oUserModel->getSex($oSession->get('member_id'));
            $aMatchSex = Form::getVal($oUserModel->getMatchSex($oSession->get('member_id')));
        }

        return ['user_sex' => $sUserSex, 'match_sex' => $aMatchSex];
    }

    /**
     * If a user is logged, get "approximately" the relative age for better and more intuitive search.
     *
     * @param UserCoreModel $oUserModel
     * @param Session $oSession
     *
     * @return array 'min_age' and 'max_age' which is the approximately age the user is looking for.
     */
    protected static function getAgeVals(UserCoreModel $oUserModel, Session $oSession)
    {
        $iMinAge = (int)DbConfig::getSetting('minAgeRegistration');
        $iMaxAge = (int)DbConfig::getSetting('maxAgeRegistration');

        if (UserCore::auth()) {
            $sBirthDate = $oUserModel->getBirthDate($oSession->get('member_id'));
            $iAge = UserBirthDateCore::getAgeFromBirthDate($sBirthDate);

            $iMinAge = ($iAge - 5 < $iMinAge) ? $iMinAge : $iAge - 5;
            $iMaxAge = ($iAge + 5 > $iMaxAge) ? $iMaxAge : $iAge + 5;
        }

        return ['min_age' => $iMinAge, 'max_age' => $iMaxAge];
    }

    /**
     * Set the default values for the fields in search forms.
     *
     * @return void
     */
    protected static function setAttrVals()
    {
        $oHttpRequest = new HttpRequest;
        $oSession = new Session;
        $oUserModel = new UserCoreModel;

        if ($oHttpRequest->getExists('match_sex')) {
            self::$aSexOption += ['value' => $oHttpRequest->get('match_sex')];
        } else {
            self::$aSexOption += ['value' => self::getGenderVals($oUserModel, $oSession)['user_sex']];
        }

        if ($oHttpRequest->getExists('sex')) {
            self::$aMatchSexOption += ['value' => $oHttpRequest->get('sex')];
        } else {
            self::$aMatchSexOption += ['value' => self::getGenderVals($oUserModel, $oSession)['match_sex']];
        }

        self::$aAgeOption = ['value' => self::getAgeVals($oUserModel, $oSession)];
        if ($oHttpRequest->getExists(['age1', 'age2'])) {
            self::$aAgeOption = [
                'value' => [
                    'min_age' => $oHttpRequest->get('age1'),
                    'max_age' => $oHttpRequest->get('age2')
                ]
            ];
        }

        if ($oHttpRequest->getExists(SearchQueryCore::COUNTRY)) {
            self::$aCountryOption += ['value' => $oHttpRequest->get(SearchQueryCore::COUNTRY)];
        } else {
            self::$aCountryOption += ['value' => Geo::getCountryCode()];
        }

        if ($oHttpRequest->getExists(SearchQueryCore::CITY)) {
            $sCity = $oHttpRequest->get(SearchQueryCore::CITY);
        } else {
            $sCity = Geo::getCity();
        }
        self::$aCityOption += ['value' => $sCity, 'onfocus' => "if('" . $sCity . "' == this.value) this.value = '';", 'onblur' => "if ('' == this.value) this.value = '" . $sCity . "';"];

        self::$aStateOption += ['value' => Geo::getState(), 'onfocus' => "if('" . Geo::getState() . "' == this.value) this.value = '';", 'onblur' => "if ('' == this.value) this.value = '" . Geo::getState() . "';"];

        if ($oHttpRequest->getExists(SearchQueryCore::ORDER)) {
            self::$aLatestOrder += ['value' => SearchCoreModel::LATEST];
        }

        if ($oHttpRequest->getExists(SearchQueryCore::AVATAR)) {
            self::$aAvatarOnly += ['value' => '1'];
        }

        if ($oHttpRequest->getExists(SearchQueryCore::ONLINE)) {
            self::$aOnlineOnly += ['value' => '1'];
        }
    }
}
