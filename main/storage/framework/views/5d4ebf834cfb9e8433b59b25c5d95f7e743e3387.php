<?php $__env->startSection('element'); ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><?php echo e(__('Create AI Configuration')); ?></h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.ai-configuration.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <?php if($errors->any()): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo e($error); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo e(__('Provider')); ?> <span class="text-danger">*</span></label>
                                    <select name="provider" id="provider" class="form-control" required onchange="updateDefaults()">
                                        <option value=""><?php echo e(__('Select Provider')); ?></option>
                                        <?php $__currentLoopData = $availableProviders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($provider); ?>" <?php echo e(old('provider') == $provider ? 'selected' : ''); ?>>
                                                <?php echo e(ucfirst($provider)); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo e(__('Configuration Name')); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?php echo e(old('name')); ?>" required placeholder="e.g., OpenAI Production">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo e(__('API Key')); ?> <span class="text-danger">*</span></label>
                            <input type="password" name="api_key" class="form-control" value="<?php echo e(old('api_key')); ?>" required>
                            <small class="text-muted"><?php echo e(__('Your API key will be encrypted and stored securely')); ?></small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo e(__('API URL')); ?></label>
                                    <input type="url" name="api_url" id="api_url" class="form-control" value="<?php echo e(old('api_url')); ?>" placeholder="Leave empty for default">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo e(__('Model')); ?></label>
                                    <div class="input-group">
                                        <select name="model" id="model" class="form-control">
                                            <option value=""><?php echo e(__('Select a model')); ?></option>
                                            <?php if(old('model')): ?>
                                                <option value="<?php echo e(old('model')); ?>" selected><?php echo e(old('model')); ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" id="refresh-models-btn" style="display: none;">
                                                <i class="fa fa-refresh"></i> <?php echo e(__('Refresh')); ?>

                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted" id="model-help-text"><?php echo e(__('Enter API key and select provider to fetch available models')); ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo e(__('Priority')); ?></label>
                                    <input type="number" name="priority" class="form-control" value="<?php echo e(old('priority', 50)); ?>" min="0" max="100">
                                    <small class="text-muted"><?php echo e(__('Higher = tried first')); ?></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo e(__('Timeout (seconds)')); ?></label>
                                    <input type="number" name="timeout" class="form-control" value="<?php echo e(old('timeout', 30)); ?>" min="5" max="300">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo e(__('Temperature')); ?></label>
                                    <input type="number" name="temperature" step="0.1" class="form-control" value="<?php echo e(old('temperature', 0.3)); ?>" min="0" max="2">
                                    <small class="text-muted"><?php echo e(__('0.0 - 2.0')); ?></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo e(__('Max Tokens')); ?></label>
                                    <input type="number" name="max_tokens" class="form-control" value="<?php echo e(old('max_tokens', 500)); ?>" min="50" max="4000">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="enabled" class="form-check-input" id="enabled" value="1" <?php echo e(old('enabled', true) ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="enabled">
                                    <?php echo e(__('Enable this configuration')); ?>

                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?php echo e(__('Create Configuration')); ?>

                            </button>
                            <a href="<?php echo e(route('admin.ai-configuration.index')); ?>" class="btn btn-secondary">
                                <?php echo e(__('Cancel')); ?>

                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const defaultConfigs = <?php echo json_encode($defaultConfigs ?? [], 15, 512) ?>;
        let availableModels = [];

        function updateDefaults() {
            const provider = document.getElementById('provider').value;
            if (defaultConfigs[provider]) {
                const config = defaultConfigs[provider];
                if (config.api_url) {
                    document.getElementById('api_url').value = config.api_url;
                }
                if (config.model) {
                    document.getElementById('model').value = config.model;
                }
            }
            
            // Show/hide refresh button based on provider
            const refreshBtn = document.getElementById('refresh-models-btn');
            const modelContainer = refreshBtn ? refreshBtn.closest('.input-group') : null;
            const modelSelect = document.getElementById('model');
            const apiKeyInput = document.querySelector('input[name="api_key"]');
            const helpText = document.getElementById('model-help-text');
            
            if (provider === 'gemini') {
                // Ensure we have a select element
                if (modelSelect && modelSelect.tagName !== 'SELECT') {
                    // Replace input with select
                    const select = document.createElement('select');
                    select.name = 'model';
                    select.id = 'model';
                    select.className = 'form-control';
                    select.innerHTML = '<option value=""><?php echo e(__('Select a model')); ?></option>';
                    if (modelSelect.value) {
                        select.innerHTML += `<option value="${modelSelect.value}" selected>${modelSelect.value}</option>`;
                    }
                    modelSelect.parentElement.replaceChild(select, modelSelect);
                }
                
                if (refreshBtn) refreshBtn.style.display = 'inline-block';
                if (helpText) helpText.textContent = '<?php echo e(__('Click Refresh to fetch available models')); ?>';
                
                // Auto-fetch if API key is already entered
                if (apiKeyInput && apiKeyInput.value.trim()) {
                    setTimeout(fetchModels, 500);
                }
            } else {
                // For non-Gemini providers, show text input
                if (refreshBtn) refreshBtn.style.display = 'none';
                if (modelSelect && modelSelect.tagName === 'SELECT') {
                    // Replace select with input
                    const textInput = document.createElement('input');
                    textInput.type = 'text';
                    textInput.name = 'model';
                    textInput.id = 'model';
                    textInput.className = 'form-control';
                    textInput.value = modelSelect.value || '';
                    textInput.placeholder = 'e.g., gpt-3.5-turbo';
                    modelSelect.parentElement.replaceChild(textInput, modelSelect);
                }
                if (helpText) helpText.textContent = '<?php echo e(__('Enter model name manually')); ?>';
            }
        }

        function fetchModels() {
            const provider = document.getElementById('provider').value;
            const apiKeyInput = document.querySelector('input[name="api_key"]');
            const modelSelect = document.getElementById('model');
            const refreshBtn = document.getElementById('refresh-models-btn');
            const helpText = document.getElementById('model-help-text');
            
            if (!provider || provider !== 'gemini') {
                return;
            }
            
            const apiKey = apiKeyInput ? apiKeyInput.value.trim() : '';
            if (!apiKey) {
                alert('<?php echo e(__('Please enter API key first')); ?>');
                return;
            }
            
            // Show loading state
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?php echo e(__('Loading...')); ?>';
            helpText.textContent = '<?php echo e(__('Fetching models...')); ?>';
            modelSelect.disabled = true;
            
            fetch('<?php echo e(route("admin.ai-configuration.fetch-models")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                },
                body: JSON.stringify({
                    provider: provider,
                    api_key: apiKey
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.models && data.models.length > 0) {
                    availableModels = data.models;
                    
                    // Clear existing options except the first one
                    modelSelect.innerHTML = '<option value=""><?php echo e(__('Select a model')); ?></option>';
                    
                    // Add fetched models
                    data.models.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.name;
                        option.textContent = model.displayName || model.name;
                        if (model.description) {
                            option.title = model.description;
                        }
                        modelSelect.appendChild(option);
                    });
                    
                    // Select default if available
                    const defaultModel = defaultConfigs[provider]?.model;
                    if (defaultModel) {
                        const defaultOption = Array.from(modelSelect.options).find(opt => opt.value === defaultModel);
                        if (defaultOption) {
                            modelSelect.value = defaultModel;
                        }
                    }
                    
                    helpText.textContent = `<?php echo e(__('Found')); ?> ${data.models.length} <?php echo e(__('available models')); ?>`;
                } else {
                    helpText.textContent = data.message || '<?php echo e(__('No models found or failed to fetch')); ?>';
                    if (data.message) {
                        alert(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching models:', error);
                helpText.textContent = '<?php echo e(__('Error fetching models. Please try again.')); ?>';
                alert('<?php echo e(__('Error fetching models:')); ?> ' + error.message);
            })
            .finally(() => {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fa fa-refresh"></i> <?php echo e(__('Refresh')); ?>';
                modelSelect.disabled = false;
            });
        }

        // Auto-fetch when API key is entered (with debounce)
        let fetchTimeout;
        document.addEventListener('DOMContentLoaded', function() {
            const providerSelect = document.getElementById('provider');
            const apiKeyInput = document.querySelector('input[name="api_key"]');
            const refreshBtn = document.getElementById('refresh-models-btn');
            
            if (providerSelect) {
                providerSelect.addEventListener('change', updateDefaults);
            }
            
            if (refreshBtn) {
                refreshBtn.addEventListener('click', fetchModels);
            }
            
            if (apiKeyInput) {
                apiKeyInput.addEventListener('input', function() {
                    clearTimeout(fetchTimeout);
                    const provider = providerSelect ? providerSelect.value : '';
                    if (provider === 'gemini' && this.value.trim().length > 10) {
                        // Auto-fetch after 1 second of no typing
                        fetchTimeout = setTimeout(() => {
                            fetchModels();
                        }, 1000);
                    }
                });
            }
            
            // Initialize on page load
            updateDefaults();
        });
    </script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/addons/multi-channel-signal-addon/resources/views/backend/ai-configuration/create.blade.php ENDPATH**/ ?>