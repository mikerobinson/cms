<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

/**
 * Class EntryType record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryType extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'entrytypes';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'section'     => array(static::BELONGS_TO, 'Section', 'required' => true, 'onDelete' => static::CASCADE),
			'fieldLayout' => array(static::BELONGS_TO, 'FieldLayout', 'onDelete' => static::SET_NULL),
		);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name', 'sectionId'), 'unique' => true),
			array('columns' => array('handle', 'sectionId'), 'unique' => true),
		);
	}

	/**
	 * @inheritDoc BaseRecord::rules()
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		if (!$this->hasTitleField)
		{
			$rules[] = array('titleFormat', 'required');
		}

		return $rules;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'          => array(AttributeType::Name, 'required' => true),
			'handle'        => array(AttributeType::Handle, 'required' => true),
			'hasTitleField' => array(AttributeType::Bool, 'required' => true, 'default' => true),
			'titleLabel'    => array(AttributeType::String, 'default' => 'Title'),
			'titleFormat'   => AttributeType::String,
			'sortOrder'     => AttributeType::SortOrder,
		);
	}
}