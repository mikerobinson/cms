<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use yii\base\Component;
use craft\app\models\Et                 as EtModel;
use craft\app\models\Update             as UpdateModel;
use craft\app\models\UpgradePurchase    as UpgradePurchaseModel;
use craft\app\web\Application;

/**
 * Class Et service.
 *
 * An instance of the Et service is globally accessible in Craft via [[Application::et `craft()->et`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Et extends Component
{
	// Constants
	// =========================================================================

	const Ping              = '@@@elliottEndpointUrl@@@actions/elliott/app/ping';
	const CheckForUpdates   = '@@@elliottEndpointUrl@@@actions/elliott/app/checkForUpdates';
	const TransferLicense   = '@@@elliottEndpointUrl@@@actions/elliott/app/transferLicenseToCurrentDomain';
	const GetEditionInfo    = '@@@elliottEndpointUrl@@@actions/elliott/app/getEditionInfo';
	const PurchaseUpgrade   = '@@@elliottEndpointUrl@@@actions/elliott/app/purchaseUpgrade';
	const GetUpdateFileInfo = '@@@elliottEndpointUrl@@@actions/elliott/app/getUpdateFileInfo';

	// Public Methods
	// =========================================================================

	/**
	 * @return EtModel|null
	 */
	public function ping()
	{
		$et = new Et(static::Ping);
		$etResponse = $et->phoneHome();
		return $etResponse;
	}

	/**
	 * Checks if any new updates are available.
	 *
	 * @param $updateInfo
	 *
	 * @return EtModel|null
	 */
	public function checkForUpdates($updateInfo)
	{
		$et = new Et(static::CheckForUpdates);
		$et->setData($updateInfo);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			$etResponse->data = new UpdateModel($etResponse->data);
			return $etResponse;
		}
	}

	/**
	 * @return EtModel|null
	 */
	public function getUpdateFileInfo()
	{
		$et = new Et(static::GetUpdateFileInfo);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			return $etResponse->data;
		}
	}

	/**
	 * @param string $downloadPath
	 * @param string $md5
	 *
	 * @return bool
	 */
	public function downloadUpdate($downloadPath, $md5)
	{
		if (IOHelper::folderExists($downloadPath))
		{
			$downloadPath .= $md5.'.zip';
		}

		$updateModel = craft()->updates->getUpdates();
		$buildVersion = $updateModel->app->latestVersion.'.'.$updateModel->app->latestBuild;

		$path = 'http://download.buildwithcraft.com/craft/'.$updateModel->app->latestVersion.'/'.$buildVersion.'/Patch/'.$updateModel->app->localBuild.'/'.$md5.'.zip';

		$et = new Et($path, 240);
		$et->setDestinationFileName($downloadPath);

		if (($fileName = $et->phoneHome()) !== null)
		{
			return $fileName;
		}

		return false;
	}

	/**
	 * Transfers the installed license to the current domain.
	 *
	 * @return true|string Returns true if the request was successful, otherwise returns the error.
	 */
	public function transferLicenseToCurrentDomain()
	{
		$et = new Et(static::TransferLicense);
		$etResponse = $et->phoneHome();

		if (!empty($etResponse->data['success']))
		{
			return true;
		}
		else
		{
			// Did they at least say why?
			if (!empty($etResponse->errors))
			{
				switch ($etResponse->errors[0])
				{
					// Validation errors
					case 'not_public_domain':
					{
						// So...
						return true;
					}

					default:
					{
						$error = $etResponse->data['error'];
					}
				}
			}
			else
			{
				$error = Craft::t('Craft is unable to transfer your license to this domain at this time.');
			}

			return $error;
		}
	}

	/**
	 * Fetches info about the available Craft editions from Elliott.
	 *
	 * @return EtModel|null
	 */
	public function fetchEditionInfo()
	{
		$et = new Et(static::GetEditionInfo);
		$etResponse = $et->phoneHome();
		return $etResponse;
	}

	/**
	 * Attempts to purchase an edition upgrade.
	 *
	 * @param UpgradePurchaseModel $model
	 *
	 * @return bool
	 */
	public function purchaseUpgrade(UpgradePurchaseModel $model)
	{
		if ($model->validate())
		{
			$et = new Et(static::PurchaseUpgrade);
			$et->setData($model);
			$etResponse = $et->phoneHome();

			if (!empty($etResponse->data['success']))
			{
				// Success! Let's get this sucker installed.
				craft()->setEdition($model->edition);

				return true;
			}
			else
			{
				// Did they at least say why?
				if (!empty($etResponse->errors))
				{
					switch ($etResponse->errors[0])
					{
						// Validation errors
						case 'edition_doesnt_exist': $error = Craft::t('The selected edition doesn’t exist anymore.'); break;
						case 'invalid_license_key':  $error = Craft::t('Your license key is invalid.'); break;
						case 'license_has_edition':  $error = Craft::t('Your Craft license already has this edition.'); break;
						case 'price_mismatch':       $error = Craft::t('The cost of this edition just changed.'); break;
						case 'unknown_error':        $error = Craft::t('An unknown error occurred.'); break;

						// Stripe errors
						case 'incorrect_number':     $error = Craft::t('The card number is incorrect.'); break;
						case 'invalid_number':       $error = Craft::t('The card number is invalid.'); break;
						case 'invalid_expiry_month': $error = Craft::t('The expiration month is invalid.'); break;
						case 'invalid_expiry_year':  $error = Craft::t('The expiration year is invalid.'); break;
						case 'invalid_cvc':          $error = Craft::t('The security code is invalid.'); break;
						case 'incorrect_cvc':        $error = Craft::t('The security code is incorrect.'); break;
						case 'expired_card':         $error = Craft::t('Your card has expired.'); break;
						case 'card_declined':        $error = Craft::t('Your card was declined.'); break;
						case 'processing_error':     $error = Craft::t('An error occurred while processing your card.'); break;

						default:                     $error = $etResponse->errors[0];
					}
				}
				else
				{
					// Something terrible must have happened!
					$error = Craft::t('Craft is unable to purchase an edition upgrade at this time.');
				}

				$model->addError('response', $error);
			}
		}

		return false;
	}

	/**
	 * Returns the license key status, or false if it's unknown.
	 *
	 * @return string|false
	 */
	public function getLicenseKeyStatus()
	{
		return craft()->cache->get('licenseKeyStatus');
	}

	/**
	 * Returns the domain that the installed license key is licensed for, null if it's not set yet, or false if it's
	 * unknown.
	 *
	 * @return string|null|false
	 */
	public function getLicensedDomain()
	{
		return craft()->cache->get('licensedDomain');
	}

	/**
	 * Creates a new EtModel with provided JSON, and returns it if it's valid.
	 *
	 * @param array $attributes
	 *
	 * @return EtModel|null
	 */
	public function decodeEtModel($attributes)
	{
		if ($attributes)
		{
			$attributes = JsonHelper::decode($attributes);

			if (is_array($attributes))
			{
				$etModel = new EtModel($attributes);

				// Make sure it's valid. (At a minimum, localBuild and localVersion
				// should be set.)
				if ($etModel->validate())
				{
					return $etModel;
				}
			}
		}
	}
}