<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\helpers\DbHelper;
use yii\db\Schema;

/**
 * Date represents a Date/Time field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Date extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Date/Time');
	}

	// Properties
	// =========================================================================

	/**
	 * @var boolean Whether a datepicker should be shown as part of the input
	 */
	public $showDate = true;

	/**
	 * @var boolean Whether a timepicker should be shown as part of the input
	 */
	public $showTime = false;

	/**
	 * @var integer The number of minutes that the timepicker options should increment by
	 */
	public $minuteIncrement = 30;

	// Public Methods
	// =========================================================================

	public function init()
	{
		parent::init();

		// In case nothing is selected, default to the date.
		if (!$this->showDate && !$this->showTime)
		{
			$this->showDate = true;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['showDate', 'showTime'], 'boolean'];
		$rules[] = [['minuteIncrement'], 'integer', 'min' => 1, 'max' => 60];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getContentColumnType()
	{
		return Schema::TYPE_DATETIME;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		// If they are both selected or nothing is selected, the select showBoth.
		if (($this->showDate && $this->showTime))
		{
			$dateTimeValue = 'showBoth';
		}
		else if ($this->showDate)
		{
			$dateTimeValue = 'showDate';
		}
		else if ($this->showTime)
		{
			$dateTimeValue = 'showTime';
		}

		$options = [15, 30, 60];
		$options = array_combine($options, $options);

		return Craft::$app->templates->render('_components/fieldtypes/Date/settings', [
			'options' => [
				[
					'label' => Craft::t('app', 'Show date'),
					'value' => 'showDate',
				],
				[
					'label' => Craft::t('app', 'Show time'),
					'value' => 'showTime',
				],
				[
					'label' => Craft::t('app', 'Show date and time'),
					'value' => 'showBoth',
				]
			],
			'value' => $dateTimeValue,
			'incrementOptions' => $options,
			'field' => $this,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($name, $value)
	{
		$variables = [
			'id'              => Craft::$app->templates->formatInputId($name),
			'name'            => $name,
			'value'           => $value,
			'minuteIncrement' => $this->minuteIncrement
		];

		$input = '';

		if ($this->showDate)
		{
			$input .= Craft::$app->templates->render('_includes/forms/date', $variables);
		}

		if ($this->showTime)
		{
			$input .= ' '.Craft::$app->templates->render('_includes/forms/time', $variables);
		}

		return $input;
	}

	/**
	 * @inheritdoc
	 */
	public function prepValue($value)
	{
		if ($value)
		{
			// Set it to the system timezone
			$timezone = Craft::$app->getTimeZone();
			$value->setTimezone(new \DateTimeZone($timezone));

			return $value;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function modifyElementsQuery(ElementQueryInterface $query, $value)
	{
		if ($value !== null)
		{
			$handle = $this->handle;
			/** @var ElementQuery $query */
			$query->subQuery->andWhere(DbHelper::parseDateParam('content.'.Craft::$app->content->fieldColumnPrefix.$handle, $value, $query->subQuery->params));
		}
	}

	/**
	 * @inheritdoc
	 */
	public function prepSettings($settings)
	{
		if (isset($settings['dateTime']))
		{
			switch ($settings['dateTime'])
			{
				case 'showBoth':
				{
					unset($settings['dateTime']);
					$settings['showTime'] = true;
					$settings['showDate'] = true;

					break;
				}
				case 'showDate':
				{
					unset($settings['dateTime']);
					$settings['showDate'] = true;
					$settings['showTime'] = false;

					break;
				}
				case 'showTime':
				{
					unset($settings['dateTime']);
					$settings['showTime'] = true;
					$settings['showDate'] = false;

					break;
				}
			}
		}

		return $settings;
	}
}