<?php
 

namespace Modules\Documents\App\Mail;

class SendDocument extends DocumentMailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('documents::mail.send')
            ->subject($this->document->data['send_mail_subject']);
    }
}
