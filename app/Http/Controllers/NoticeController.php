<?php namespace App\Http\Controllers;

class NoticeController extends Controller {

    public function notice($type = 'toast', $userId = null, $message = 'Your notice here')
    {
        $this->client->send(json_encode([
            "channel" => $type,
            'id'      => $userId,
            'message' => $message
        ]));
    }

    public function queue()
    {
        \Queue::push(function($job)
        {
            //sleep(60);

            \Log::info('Socket');

            $job->delete();

            $this->client->send(json_encode([
                "channel" => 'toast',
                'id'      => 2,
                'message' => 'System message for user 2'
            ]));
        });
    }

}
