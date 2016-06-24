<?php

namespace Orchestra\Notifications;

use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\SendQueuedNotifications;

trait RoutesNotifications
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $instance
     * @return void
     */
    public function notify($instance)
    {
        $manager = app(ChannelManager::class);

        $notifications = $manager->notificationsFromInstance(
            $this, $instance
        );

        if ($instance instanceof ShouldQueue) {
            return $this->queueNotifications($instance, $notifications);
        }

        foreach ($notifications as $notification) {
            $manager->send($notification);
        }
    }

    /**
     * Send the given notification via the given channels.
     *
     * @param  array|string  $channels
     * @param  mixed  $instance
     * @return void
     */
    public function notifyVia($channels, $instance)
    {
        $manager = app(ChannelManager::class);

        $notifications = $manager->notificationsFromInstance(
            $this, $instance, (array) $channels
        );

        foreach ($notifications as $notification) {
            $notification->via((array) $channels);
        }

        if ($instance instanceof ShouldQueue) {
            return $this->queueNotifications($instance, $notifications);
        }

        foreach ($notifications as $notification) {
            $manager->send($notification);
        }
    }

    /**
     * Queue the given notification instances.
     *
     * @param  mixed  $instance
     * @param  array[\Illuminate\Notifcations\Channels\Notification]
     * @return void
     */
    protected function queueNotifications($instance, array $notifications)
    {
        dispatch(
            (new SendQueuedNotifications($notifications))
                    ->onConnection($instance->connection)
                    ->onQueue($instance->queue)
                    ->delay($instance->delay)
        );
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        if (method_exists($this, $method = 'routeNotificationFor'.Str::studly($driver))) {
            return $this->{$method}();
        }

        switch ($driver) {
            case 'database':
                return $this->notifications();
            case 'mail':
                return $this->getRecipientEmail();
            case 'nexmo':
                return $this->phone_number;
        }
    }
}
