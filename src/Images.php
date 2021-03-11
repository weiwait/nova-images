<?php

namespace Weiwait\NovaImages;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Http\Requests\NovaRequest;

class Images extends File
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-images';

    public function __construct($name, $attribute = null, $disk = 'public', $storageCallback = null)
    {
        if (!(is_null($this->value) || is_array($this->value) || $this->value instanceof Arrayable)) {
            throw new \Exception('The value must be arrayable');
        }

        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->acceptedTypes('image/*');

        $this->thumbnail(function () {
            return $this->value ? Storage::disk($this->getStorageDisk())->url(Arr::first($this->value)) : null;
        })->preview(function () {
            return $this->value ? $this->storageUrls($this->value) : null;
        });
    }

    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute): void
    {
        if ($request->exists($requestAttribute)) {
            $data = is_array($request[$requestAttribute]) ? $request[$requestAttribute] : json_decode($request[$requestAttribute], true);

            $paths = [];
            foreach ($data as $item) {
                if (!is_array($item)) {
                    $paths[] = $item;
                    continue;
                }
                if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $item['image'], $res)) {
                    $filename = 'images/' . md5(Str::uuid()) . '.' . $res[2];
                    Storage::disk($this->getStorageDisk())->put($filename, base64_decode(str_replace($res[1], '', $item['image'])));
                    $paths[] = $filename;
                }
            }

            $model->{$attribute} = $paths;
            if ($items = $model->getOriginal()[$requestAttribute]) {
                foreach ($items as $item) {
                    if (!in_array($item, $paths)) {
                        Storage::disk($this->getStorageDisk())->delete($item);
                    }
                }
            }
        }
    }

    /**
     * vue-cropper 配置项
     * @param array $options
     * @return Images
     */
    public function cropper(array $options = []): Images
    {
        return $this->withMeta(['cropper' => $options]);
    }

    protected function mergeExtraStorageColumns($request, array $attributes): array
    {
        return $attributes;
    }

    protected function storageUrls(array $paths): array
    {
        foreach ($paths as &$path) {
            $path = Storage::disk($this->getStorageDisk())->url($path);
        }

        return $paths;
    }
}
