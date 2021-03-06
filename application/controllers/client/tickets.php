<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tickets extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		
		check_user_permissions();
		
		$this->form_validation->set_error_delimiters('<p class="help-block">', '</p>');
		
		$this->data['folder_name'] = 'client/tickets/';
	}
	
	public function index()
	{
		$user = $this->data['user'] = $this->ion_auth->get_user(user_id());
		$settings = $this->data['settings'] = $this->settings->get_settings();
		if(isset($_POST['new_ticket'])){ // Quick and dirty - add a new ticket
			$this->form_validation->set_rules('subject', 'Ticket Subject', 'required|trim|xss_clean');
			$this->form_validation->set_rules('issue', 'Issue Description', 'required|trim|xss_clean');
			$this->form_validation->set_rules('project', 'Project', 'required');
			if ($this->form_validation->run() == TRUE)
			{
				$query = $this->db->query("INSERT INTO tickets (code, subject, issue, client, project, status) VALUES ('".$this->generate_ticket_code(5)."', '".mysql_real_escape_string($_POST['subject'])."', '".mysql_real_escape_string($_POST['issue'])."', '".user_id()."', '$_POST[project]', 'Open')");
				$ticket_id = $this->db->insert_id();
				if($query){
					$project = $this->core->get_project($_POST['project']);
					// $ticket = $this->data['ticket'] = $this->core->get_ticket($this->db->insert_id());
					// Send Email
					$email_data['username'] = $user->username;
					$email_data['subject'] = $_POST['subject'];
					$email_data['ticket_id'] = $ticket_id;
					foreach($this->core->get_admin_emails() as $email){
						$this->email->from($settings['company_email'], $settings['site_name']);
						$this->email->to($email); 
						$this->email->subject('New Support Ticket Opened');
						$this->email->message($this->load->view('emails/new_ticket', $email_data, true));	
						$this->email->send();
					}
					flashmsg('New ticket created for project: '.$project->name.'.', 'success');
					redirect('/client/tickets');
				}
			}
		}
		$all_projects = $this->core->get_projects();
		$projects = array('' => 'Select one');
		foreach ($all_projects as $project)
		{
			$projects[$project->id] = $project->name;
		}
		$this->data['projects'] = $projects;
		$tickets = $this->data['tickets'] = $this->core->get_client_tickets(user_id());
		// pagination
		$this->data['base_pagination'] = base_url('client/tickets/page/');
		$this->data['total_rows'] = count($tickets);
		$this->data['per_page'] = 10; 
		$this->data['row_start'] = intval($this->uri->segment(4));
		$this->data['links'] = pagination_links($this->data);
		// end pagination
		$this->data['meta_title'] = 'Your Tickets';
	}
	
	public function page()
	{
		if(isset($_POST['new_ticket'])){ // Quick and dirty - add a new ticket
			$this->form_validation->set_rules('subject', 'Ticket Subject', 'required|trim|xss_clean');
			$this->form_validation->set_rules('issue', 'Issue Description', 'required|trim|xss_clean');
			$this->form_validation->set_rules('project', 'Project', 'required');
			if ($this->form_validation->run() == TRUE)
			{
				$query = $this->db->query("INSERT INTO tickets (code, subject, issue, client, project, status) VALUES ('".$this->generate_ticket_code(5)."', '".mysql_real_escape_string($_POST['subject'])."', '".mysql_real_escape_string($_POST['issue'])."', '".user_id()."', '$_POST[project]', 'Open')");
				if($query){
					$project = $this->core->get_project($_POST['project']);
					flashmsg('New ticket created for project: '.$project->name.'.', 'success');
					redirect('/client/tickets');
				}
			}
		}
		$all_projects = $this->core->get_projects();
		$projects = array('' => 'Select one');
		foreach ($all_projects as $project)
		{
			$projects[$project->id] = $project->name;
		}
		$this->data['projects'] = $projects;
		$tickets = $this->data['tickets'] = $this->core->get_client_tickets(user_id());
		// pagination
		$this->data['base_pagination'] = base_url('client/tickets/page/');
		$this->data['total_rows'] = count($tickets);
		$this->data['per_page'] = 10; 
		$this->data['row_start'] = intval($this->uri->segment(4));
		$this->data['links'] = pagination_links($this->data);
		// end pagination
		$this->data['meta_title'] = 'Your Tickets';
	}
	
	public function view($id = NULL)
	{
		$user = $this->data['user'] = $this->ion_auth->get_user(user_id());
		$ticket = $this->data['ticket'] = $this->core->get_ticket($id);
		$settings = $this->data['settings'] = $this->settings->get_settings();
		if($ticket->client!=$user->id){
			flashmsg('Ticket does not exist', 'error');
			redirect('client/tickets');
		}
		$replies = $this->data['replies'] = $this->core->get_ticket_replies($ticket->code);
		if(isset($_POST['reply'])){ // Quick and dirty - reply
			$this->form_validation->set_rules('reply', 'Reply', 'required|trim|xss_clean');
			if ($this->form_validation->run() == TRUE)
			{
				if(count($replies)==0){
					$subject = $ticket->subject;
				} else {
					$subject = $replies[count($replies)-1]->subject;
				}
				$query = $this->db->query("INSERT INTO tickets (code, subject, issue, client, project, status, reply) VALUES ('$ticket->code', 'RE: $subject', '<b>$user->username says:</b> ".mysql_real_escape_string($_POST['reply'])."', '$ticket->client', '$ticket->project', 'Open', 1)");
				if($query){
					// Send Email
					$email_data['user'] = $user->username;
					$email_data['ticket_subject'] = 'RE: '.$subject;
					$email_data['reply'] = '<b>'.$user->username.' says:</b> '.$_POST['reply'];
					$email_data['ticket_id'] = $ticket->code;
					foreach($this->core->get_admin_emails() as $email){
						$this->email->from($settings['company_email'], $settings['site_name']);
						$this->email->to($email); 
						$this->email->subject('New Ticket Reply');
						$this->email->message($this->load->view('emails/ticket_reply', $email_data, true));	
						$this->email->send();
					}
					flashmsg('New reply successfully added to ticket', 'success');
					redirect('/client/tickets/view/'.$id);
				}
			}
		}
	}
	
	public function close($id = NULL)
	{
		if (empty($id))
		{
			flashmsg('You must specify a ticket to close.', 'error');
			redirect('/client/tickets');
		}
		$ticket = $this->data['ticket'] = $this->core->get_ticket($id);
		$this->form_validation->set_rules('confirm', 'confirmation', 'required');
		$this->form_validation->set_rules('id', 'ticket ID', 'required|is_natural');

		if ($this->form_validation->run() === TRUE)
		{
			// Do we really want to deactivate?
			if ($this->input->post('confirm') == 'yes')
			{
				$this->core->close_ticket($ticket->code);
				// Redirect them back to the admin page
				flashmsg('Ticket closed successfully.', 'success');
				redirect('/client/tickets');
			}
			else
			{
				redirect('/client/tickets');
			}
		}
		$this->data['meta_title'] = 'Close Ticket #'.$this->data['ticket']->code;
	}
	
	public function open($id = NULL)
	{
		if (empty($id))
		{
			flashmsg('You must specify a ticket to re-open.', 'error');
			redirect('/client/tickets');
		}
		$ticket = $this->data['ticket'] = $this->core->get_ticket($id);
		$this->form_validation->set_rules('confirm', 'confirmation', 'required');
		$this->form_validation->set_rules('id', 'ticket ID', 'required|is_natural');

		if ($this->form_validation->run() === TRUE)
		{
			// Do we really want to deactivate?
			if ($this->input->post('confirm') == 'yes')
			{
				$this->core->open_ticket($ticket->code);
				// Redirect them back to the admin page
				flashmsg('Ticket re-opened successfully.', 'success');
				redirect('/client/tickets');
			}
			else
			{
				redirect('/client/tickets');
			}
		}
		$this->data['meta_title'] = 'Re-Open Ticket #'.$this->data['ticket']->code;
	}
	
	private function generate_ticket_code($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
	{
		$str = '';
		$count = strlen($charset);
		while ($length--) {
			$str .= strtoupper($charset[mt_rand(0, $count-1)]);
		}
		if($this->db->query("SELECT * FROM tickets WHERE code = '$str'")->num_rows()>0){
			$str = $this->generate_ticket_code(5);
		}
		return $str;
	}
	
}