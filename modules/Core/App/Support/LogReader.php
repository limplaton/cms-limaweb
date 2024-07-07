<?php
 

namespace Modules\Core\App\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class LogReader implements Arrayable, JsonSerializable
{
    protected static ?string $glob = null;

    protected LogParser $parser;

    /**
     * Initialize new LogReader instance.
     */
    public function __construct(protected array $config = [])
    {
        $this->config['date'] = array_key_exists('date', $config) ? $config['date'] : null;
        $this->parser = new LogParser;
    }

    /**
     * Add custom glob reader
     */
    public static function glob(?string $glob): void
    {
        static::$glob = $glob;
    }

    /**
     * Get the available log dates
     */
    public function getLogDates(): array
    {
        $dates = [];

        $files = glob(static::$glob ?: storage_path('logs/laravel-*.log'));

        $files = array_reverse($files);

        foreach ($files as $path) {
            $fileName = basename($path);
            preg_match('/(?<=laravel-)(.*)(?=.log)/', $fileName, $dtMatch);
            $date = $dtMatch[0];
            array_push($dates, $date);
        }

        return $dates;
    }

    /**
     * Parse a log for the given date.
     */
    public function parse(string $date): array
    {
        $logFileName = 'laravel-'.$date.'.log';
        $logFilePath = storage_path('logs/'.$logFileName);

        $content = file_get_contents($logFilePath);

        $logs = [];

        $parsed = $this->parser->parseLogContent($content);

        extract($parsed, EXTR_PREFIX_ALL, 'parsed');

        $needReFormat = in_array('Next', $parsed_headerSet);
        $newContent = null;

        foreach ($parsed_headerSet as $key => $header) {
            if (empty($parsed_dateSet[$key])) {
                $parsed_dateSet[$key] = $parsed_dateSet[$key - 1];
                $parsed_envSet[$key] = $parsed_envSet[$key - 1];
                $parsed_levelSet[$key] = $parsed_levelSet[$key - 1];
                $header = str_replace('Next', $parsed_headerSet[$key - 1], $header);
            }

            $newContent .= $header.' '.$parsed_bodySet[$key];

            $logs[] = [
                'env' => $parsed_envSet[$key],
                'type' => $parsed_levelSet[$key],
                'timestamp' => $parsed_dateSet[$key],
                'header' => $header,
                'message' => mb_convert_encoding(trim($parsed_bodySet[$key]), 'UTF-8', 'UTF-8'),
            ];
        }

        if ($needReFormat) {
            file_put_contents($logFilePath, $newContent);
        }

        return $logs;
    }

    /**
     * Get the log data
     */
    public function get(): array
    {
        $dates = $this->getLogDates();

        if (count($dates) == 0) {
            return [
                'success' => false,
                'message' => 'No logs available',
                'log_dates' => $dates,
            ];
        }

        $date = $this->config['date'] ?: $dates[0];

        if (! in_array($date, $dates)) {
            return [
                'success' => false,
                'message' => 'No log file found for the selected date',
                'log_dates' => $dates,
            ];
        }

        return [
            'log_dates' => $dates,
            'date' => $date,
            'logs' => $this->parse($date),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return $this->get();
    }
}
