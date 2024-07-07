<?php
 

namespace Modules\Deals\App\Resources\Pages;

use App\Http\View\FrontendComposers\Component;
use App\Http\View\FrontendComposers\HasSections;
use App\Http\View\FrontendComposers\HasTabs;
use App\Http\View\FrontendComposers\Section;
use App\Http\View\FrontendComposers\Tab;

class DetailComponent extends Component
{
    use HasSections, HasTabs;

    /**
     * Create new DetailComponent instance.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Build the component data
     */
    public function build(): array
    {
        return [
            'tabs' => $this->orderedTabs(),
            'sections' => $this->mergeSections(
                settings('deal_view_sections') ?: []
            ),
        ];
    }

    /**
     * Init the component data
     */
    protected function init(): void
    {
        static::registerTabs([
            Tab::make('timeline', 'timeline-tab')->panel('timeline-tab-panel')->order(10),
        ]);

        static::registerSections([
            Section::make('details', 'details-section')->heading(__('core::app.record_view.sections.details')),
            Section::make('contacts', 'contacts-section')->heading(__('contacts::contact.contacts')),
            Section::make('companies', 'companies-section')->heading(__('contacts::company.companies')),
            Section::make('media', 'media-section')->heading(__('core::app.attachments')),
        ]);
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return $this->build();
    }
}
