<?php
 

namespace Modules\Core\Tests\Feature\Fields;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Mockery\MockInterface;
use Modules\Core\App\Fields\Boolean;
use Modules\Core\App\Fields\Checkbox;
use Modules\Core\App\Fields\ColorSwatch;
use Modules\Core\App\Fields\Date;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\Email;
use Modules\Core\App\Fields\MultiSelect;
use Modules\Core\App\Fields\Number;
use Modules\Core\App\Fields\Numeric;
use Modules\Core\App\Fields\Radio;
use Modules\Core\App\Fields\Select;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Fields\Textarea;
use Modules\Core\App\Fields\Timezone;
use Modules\Core\App\Fields\Url;
use Tests\TestCase;

class CustomFieldsColumnsTest extends TestCase
{
    public function test_textarea_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock->shouldReceive('text')
            ->once()
            ->with('field_id')
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf());

        Textarea::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_boolean_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('boolean')
            ->once()
            ->with('field_id')
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('default')
            ->withArgs([false])
            ->once()
            ->andReturnSelf()
        );

        Boolean::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_numeric_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('decimal')
            ->once()
            ->withArgs(['field_id', 15, 3])
            ->andReturnUsing(function () {
                return $this->mock(ColumnDefinition::class, function (MockInterface $mock) {
                    $mock->shouldReceive('index')
                        ->once()
                        ->andReturnSelf()
                        ->getMock()
                        ->shouldReceive('nullable')
                        ->withNoArgs()
                        ->once()
                        ->andReturnSelf();
                });
            })
        );

        Numeric::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_color_swatch_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('string')
            ->with('field_id', 7)
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
        );

        ColorSwatch::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_select_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('unsignedBigInteger')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('foreign')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('references')
            ->with('id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('on')
            ->with('custom_field_options')
            ->once()
            ->andReturnSelf()
        );

        Select::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_timezone_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('string')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
        );

        Timezone::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_number_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('integer')
            ->with('field_id')
            ->once()
            ->andReturnUsing(function () {
                return $this->mock(ColumnDefinition::class, function (MockInterface $mock) {
                    $mock->shouldReceive('index')
                        ->once()
                        ->andReturnSelf()
                        ->getMock()
                        ->shouldReceive('nullable')
                        ->withNoArgs()
                        ->once()
                        ->andReturnSelf();
                });
            })
        );

        Number::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_radio_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('unsignedBigInteger')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('foreign')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('references')
            ->with('id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('on')
            ->with('custom_field_options')
            ->once()
            ->andReturnSelf()
        );

        Radio::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_url_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('string')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
        );

        Url::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_text_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('string')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
        );

        Text::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_email_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('string')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
        );

        Email::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_date_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('date')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
        );

        Date::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_datetime_custom_field_alters_column()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('dateTime')
            ->with('field_id')
            ->once()
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('nullable')
            ->withNoArgs()
            ->once()
            ->andReturnSelf()
        );

        DateTime::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_multiselect_field_does_not_create_any_columns()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock->shouldNotReceive('*'));

        MultiSelect::createValueColumn($blueprintMock, 'field_id');
    }

    public function test_checkbox_field_does_not_create_any_columns()
    {
        $blueprintMock = $this->mock(Blueprint::class, fn (MockInterface $mock) => $mock->shouldNotReceive('*'));

        Checkbox::createValueColumn($blueprintMock, 'field_id');
    }
}
