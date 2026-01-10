<?php

namespace App\Abstracts;

use App\DTOs\OrderDTO;
use App\Interfaces\OrderStorageInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseOrderStorage implements OrderStorageInterface
{
    abstract protected function getModelClass(): string;

    public function store(OrderDTO $data): Model
    {
        $modelClass = $this->getModelClass();

        $model = $modelClass::find($data->id);
        if ($model) {
            \Log::info("Order {$data->id} exists in {$modelClass}, skipping.");
            return $model;
        }
        return $modelClass::create($data->toArray());
    }

    public function find(string $id): ?Model
    {
        $modelClass = $this->getModelClass();
        return $modelClass::find($id);
    }
}
