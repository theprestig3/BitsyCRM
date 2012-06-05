<div class="admin-panel">
<div class="row-fluid">
	<div class="span6">
		<div class="page-header">
			<h3>Invoice Details</h3>
		</div>
		<?=form_open(current_url(), array('class' => 'form-horizontal', 'id' => 'generate_invoice'))?>
			<?=bootstrap_dropdown('client', 'Client', $clients, NULL, 'class="span4" onchange="get_clients_projects()"')?>
			<?=bootstrap_dropdown('project_id', 'Project', array())?>
			<?=bootstrap_input('id', 'Invoice ID')?>
			<?=bootstrap_input('description', 'Invoice Description', '...')?>
			<?=bootstrap_input('amount_paid', 'Amount Paid')?>
			<div class="items"></div>
			<div class="controls">
				<button type="button" onclick="update_preview()" class="btn btn-info">Update Preview</button>
				<button type="button" onclick="add_invoice_item()" class="btn btn-success">Add Item</button>
				<?=form_submit('new_invoice', 'Generate Invoice', 'class="btn btn-primary"')?>
			</div>
		<?=form_close()?>
	</div>
	<div class="span6">
		<div class="page-header">
			<a href="<?=base_url('admin/invoices')?>" class="btn pull-right">Back to Invoices</a>
			<h3>Preview Invoice</h3>
		</div>
		<div id="invoice-preview">Select a client to begin...</div>
	</div>
</div>