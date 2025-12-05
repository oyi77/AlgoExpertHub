<?php

namespace Addons\TradingManagement\Modules\Marketplace\Services;

use Addons\TradingManagement\Modules\Marketplace\Models\{BotTemplate, SignalSourceTemplate, CompleteBot, TemplateClone};
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TemplateCloneService
{
    public function clone(string $templateType, int $templateId, int $userId, array $customizations = []): array
    {
        try {
            DB::beginTransaction();

            $template = $this->getTemplate($templateType, $templateId);
            
            if (!$template || !$template->is_public) {
                throw new \Exception('Template not found or not public');
            }

            // Check if user already cloned
            $existingClone = TemplateClone::where('user_id', $userId)
                ->where('template_type', $templateType)
                ->where('original_id', $templateId)
                ->first();

            if ($existingClone) {
                throw new \Exception('You have already cloned this template');
            }

            // Merge template config with customizations
            $baseConfig = $template->config ?? [];
            if (method_exists($template, 'getCloneableConfig')) {
                $baseConfig = array_merge($baseConfig, [
                    'indicators_config' => $template->indicators_config ?? [],
                    'entry_rules' => $template->entry_rules ?? [],
                    'exit_rules' => $template->exit_rules ?? [],
                    'risk_config' => $template->risk_config ?? [],
                ]);
            }

            $clonedConfig = array_merge($baseConfig, $customizations);

            // Create clone record
            $clone = TemplateClone::create([
                'user_id' => $userId,
                'template_type' => $templateType,
                'template_id' => $templateId,
                'original_id' => $templateId,
                'cloned_config' => $clonedConfig,
                'custom_name' => $customizations['name'] ?? $template->name . ' (Cloned)',
                'is_active' => $customizations['activate'] ?? false,
            ]);

            // If paid template, create payment record (future enhancement)
            if ($template->price > 0) {
                // Create payment/transaction
            }

            DB::commit();

            return [
                'success' => true,
                'clone' => $clone,
                'message' => 'Template cloned successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function activate(int $cloneId, int $userId): array
    {
        try {
            $clone = TemplateClone::where('id', $cloneId)
                ->where('user_id', $userId)
                ->first();

            if (!$clone) {
                throw new \Exception('Clone not found');
            }

            // Create actual instance based on type
            switch ($clone->template_type) {
                case 'bot':
                    $this->activateBotTemplate($clone);
                    break;
                case 'signal':
                    $this->activateSignalSource($clone);
                    break;
                case 'complete':
                    $this->activateCompleteBot($clone);
                    break;
            }

            $clone->update(['is_active' => true]);

            return [
                'success' => true,
                'message' => 'Template activated successfully',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function activateBotTemplate(TemplateClone $clone)
    {
        // Create TradingPreset from bot template config
        if (class_exists('\Addons\TradingPresetAddon\App\Models\TradingPreset')) {
            \Addons\TradingPresetAddon\App\Models\TradingPreset::create([
                'user_id' => $clone->user_id,
                'name' => $clone->custom_name,
                'config' => $clone->cloned_config,
                'is_active' => true,
            ]);
        }
    }

    protected function activateSignalSource(TemplateClone $clone)
    {
        // Create ChannelSource from signal template config
        if (class_exists('\Addons\MultiChannelSignalAddon\App\Models\ChannelSource')) {
            \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::create([
                'user_id' => $clone->user_id,
                'name' => $clone->custom_name,
                'type' => $clone->cloned_config['source_type'] ?? 'telegram',
                'config' => $clone->cloned_config,
                'status' => 'active',
            ]);
        }
    }

    protected function activateCompleteBot(TemplateClone $clone)
    {
        // Create ExecutionConnection + Filter Strategy + AI config
        // Complex integration - placeholder for now
    }

    protected function getTemplate(string $type, int $id)
    {
        return match($type) {
            'bot' => BotTemplate::find($id),
            'signal' => SignalSourceTemplate::find($id),
            'complete' => CompleteBot::find($id),
            default => null,
        };
    }

    public function getUserClones(int $userId, string $type = null)
    {
        $query = TemplateClone::where('user_id', $userId)->with('template');

        if ($type) {
            $query->where('template_type', $type);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}


