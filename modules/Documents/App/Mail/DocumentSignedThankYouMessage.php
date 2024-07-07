<?php
 

namespace Modules\Documents\App\Mail;

class DocumentSignedThankYouMessage extends DocumentMailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('documents::mail.thankyou')
            ->with(['content' => $this->document->localizedBrandConfig('document.signed_mail_message')])
            ->subject($this->document->localizedBrandConfig('document.signed_mail_subject'));
    }
}
