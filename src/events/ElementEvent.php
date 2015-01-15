<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var \craft\app\models\BaseElementModel The element model associated with the event.
	 */
	public $element;
}