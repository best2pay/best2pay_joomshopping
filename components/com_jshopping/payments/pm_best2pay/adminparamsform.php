<?php
/*
* @info Платёжный модуль SpryPay для JoomShopping
* @package JoomShopping for Joomla!
* @subpackage payment
* @author SpryPay.ru
*/

defined('_JEXEC') or die();

?>
<div class="col100">
	<fieldset class="adminform">
		<table class="admintable" width="100%">
			<tr>
				<td class="key" width="300">
					<?php echo _JSHOP_CFG_BEST2PAY_SHOP_ID; ?></td>
				<td>
					<input type="text" name="pm_params[best2pay_sector_id]" class="inputbox" value="<?php echo $params['best2pay_sector_id']; ?>" />
					<?php echo JHTML::tooltip(_JSHOP_CFG_BEST2PAY_SHOP_ID_DESCRIPTION); ?>
				</td>
			</tr>
			<tr>
				<td class="key">
					<?php echo _JSHOP_CFG_BEST2PAY_PASSWORD; ?>
				</td>
				<td>
					<input type="text" name="pm_params[best2pay_password]" class="inputbox" value="<?php echo $params['best2pay_password']?>" />
					<?php echo JHTML::tooltip(_JSHOP_CFG_BEST2PAY_PASSWORD_DESCRIPTION); ?>
				</td>
			</tr>
			<tr>
				<td class="key">
					<?php echo _JSHOP_CFG_BEST2PAY_MODE; ?>
				</td>
				<td>
					<input type="checkbox" name="pm_params[best2pay_mode]" class="checkbox" value="test" <?php if ($params['best2pay_mode'] == 'test') echo 'checked'; ?> /> <?php echo _JSHOP_CFG_BEST2PAY_MODE_DESCRIPTION; ?>
				</td>
			</tr>

            <tr>
                <td class="key">
                    <?php echo _JSHOP_CFG_BEST2PAY_KKT; ?>
                </td>
                <td>
                    <input type="checkbox" name="pm_params[best2pay_kkt]" class="checkbox" value="test" <?php if ($params['best2pay_kkt'] == 'test') echo 'checked'; ?> /> <?php echo _JSHOP_CFG_BEST2PAY_KKT_DESCRIPTION; ?>
                </td>
            </tr>

            <td class="key">
                <?php echo _JSHOP_CFG_BEST2PAY_TAX; ?>
            </td>
            <td>
                <input type="text" name="pm_params[best2pay_tax]" class="inputbox" value="<?php echo $params['best2pay_tax']?>" />
                <?php echo JHTML::tooltip(_JSHOP_CFG_BEST2PAY_TAX_DESCRIPTION); ?>
            </td>
            </tr>

			<tr>
				<td class="key">
					<?php echo _JSHOP_TRANSACTION_END; ?>
				</td>
				<td>
				<?php
					echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_end_status]', 'class="inputbox" size="1"', 'status_id', 'name', $params['transaction_end_status']);
				?>
				</td>
			</tr>
			<tr>
				<td class="key">
					<?php echo _JSHOP_TRANSACTION_PENDING; ?>
				</td>
				<td>
				<?php
					echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_pending_status]', 'class="inputbox" size="1"', 'status_id', 'name', $params['transaction_pending_status']);
				?>
				</td>
			</tr>
			<tr>
				<td class="key">
					<?php echo _JSHOP_TRANSACTION_FAILED; ?>
				</td>
				<td>
				<?php
					echo JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_failed_status]', 'class="inputbox" size="1"', 'status_id', 'name', $params['transaction_failed_status']);
				?>
				</td>
			</tr>
		</table>
	</fieldset>
</div>
<div class="clr"></div>
