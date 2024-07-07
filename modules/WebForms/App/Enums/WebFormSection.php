<?php
 

namespace Modules\WebForms\App\Enums;

enum WebFormSection: string
{
    case FILE = 'file-section';
    case FIELD = 'field-section';
    case SUBMIT = 'submit-button-section';
    case MESSAGE = 'message-section';
    case INTRODUCTION = 'introduction-section';
}
