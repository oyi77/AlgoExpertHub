<?php

namespace Addons\MultiChannelSignalAddon\App\Adapters;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesConnection;
use Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesMessages;
use Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesAuthentication;
use Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesChannelManagement;

/**
 * Telegram MTProto Adapter
 * 
 * Uses user account login (like Telethon) to access private channels.
 * Requires: danog/madelineproto
 * 
 * This adapter has been refactored into traits for better organization:
 * - HandlesConnection: Connection management (connect, disconnect, getMadeline, validateConfig, getType)
 * - HandlesMessages: Message handling (fetchMessages, fetchSampleMessages)
 * - HandlesAuthentication: Authentication (startAuth, completeAuth, completePasswordAuth)
 * - HandlesChannelManagement: Channel management (getDialogs, getDialogsChunked, processDialogsChunk)
 */
class TelegramMtprotoAdapter extends BaseChannelAdapter
{
    use HandlesConnection,
        HandlesMessages,
        HandlesAuthentication,
        HandlesChannelManagement;

    /**
     * MadelineProto instance.
     *
     * @var \danog\MadelineProto\API|null
     */
    protected $madeline = null;

    /**
     * Session file path.
     *
     * @var string
     */
    protected $sessionFile;
}
