<?php
/*
* @info Платёжный модуль Best2Pay для JoomShopping
* @package JoomShopping for Joomla!
* @subpackage payment
* @author best2pay.net
*/

defined('_JEXEC') or die();

?>
<div class="col100">
	<fieldset class="adminform">
		<table class="admintable" width="100%">
			<tr>
				<td class="key" style="width:250px;">
					<?php echo \JText::_('JSHOP_CFG_BEST2PAY_SHOP_ID')?>
				</td>
				<td>
					<input type="text" name="pm_params[best2pay_sector_id]" class="inputbox form-control" value="<?php echo $params['best2pay_sector_id']; ?>" />
					<?php echo \JSHelperAdmin::tooltip(\JText::_('JSHOP_CFG_BEST2PAY_SHOP_ID_DESCRIPTION'));?>
				</td>
			</tr>
			<tr>
				<td class="key">
					<?php echo \JText::_('JSHOP_CFG_BEST2PAY_PASSWORD'); ?>
				</td>
				<td>
					<input type="text" name="pm_params[best2pay_password]" class="inputbox form-control" value="<?php echo $params['best2pay_password']?>" />
					<?php echo \JSHelperAdmin::tooltip(\JText::_('JSHOP_CFG_BEST2PAY_PASSWORD_DESCRIPTION')); ?>
				</td>
			</tr>
			<tr>
				<td class="key">
					<?php echo \JText::_('JSHOP_CFG_BEST2PAY_TESTMODE'); ?>
				</td>
				<td>
					<?php
						print \JHTML::_('select.booleanlist', 'pm_params[best2pay_testmode]', 'class = "inputbox" size = "1"', (isset($params['best2pay_testmode']) ? $params['best2pay_testmode'] : "1"));
						echo " ".\JSHelperAdmin::tooltip(\JText::_('JSHOP_CFG_BEST2PAY_TESTMODE_DESCRIPTION'));
					?>
				</td>
			</tr>
			
			<tr>
				<td class="key">
					<?php echo \JText::_('JSHOP_CFG_BEST2PAY_TAX'); ?>
				</td>
				<td>
					<?php
						echo JHTML::_('select.genericlist', $tax_list, 'pm_params[best2pay_tax]', 'class = "inputbox custom-select" size = "1" style="max-width:240px; display: inline-block"', 'status_id', 'name', $params['best2pay_tax']);
					?>
				</td>
			</tr>
			
			<tr>
				<td class="key">
					<?php echo \JText::_('JSHOP_TRANSACTION_END'); ?>
				</td>
				<td>
					<?php
					echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_end_status]', 'class = "inputbox custom-select" size = "1" style="max-width:240px; display: inline-block"', 'status_id', 'name', $params['transaction_end_status']);
					?>
				</td>
			</tr>
			<tr>
				<td class="key">
					<?php echo \JText::_('JSHOP_TRANSACTION_PENDING'); ?>
				</td>
				<td>
					<?php
					echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_pending_status]', 'class = "inputbox custom-select" size = "1" style="max-width:240px; display: inline-block"', 'status_id', 'name', $params['transaction_pending_status']);
					?>
				</td>
			</tr>
			<tr>
				<td class="key">
					<?php echo \JText::_('JSHOP_TRANSACTION_FAILED'); ?>
				</td>
				<td>
					<?php
					echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_failed_status]', 'class = "inputbox custom-select" size = "1" style="max-width:240px; display: inline-block"', 'status_id', 'name', $params['transaction_failed_status']);
					?>
				</td>
			</tr>
		</table>
	</fieldset>
</div>
<div class="clr"></div>