<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Lead;
use App\Models\Segments;

final class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $request->validate([
            'event' => ['required', 'string', 'in:lead.created,lead.updated'],
            'data' => ['required', 'array'],
        ]);

        $event = $request->input('event');
        $data = $request->input('data');

        if ($event === 'lead.created') {
            $this->handleLeadCreated($data);
        }

        if ($event === 'lead.updated') {
            $this->handleLeadUpdated($data);
        }

        return response()->json(['message' => 'Evento processado com sucesso']);
    }

    /**
     * Processa um novo lead recebido do Facebook
     */
    private function handleLeadCreated(array $data): void
    {
        Log::info('Novo lead recebido do Facebook', $data);

        try {
            // Busca o segmento padrão ou usa o primeiro disponível
            $segment = Segments::where('is_active', true)->first();

            if (!$segment) {
                Log::error('Não foi possível criar lead: nenhum segmento ativo encontrado');
                return;
            }

            $lead = new Lead();
            $lead->segment_id = $segment->id;
            $lead->name = $data['name'] ?? $data['nome'] ?? null;
            $lead->email = $data['email'] ?? null;
            $lead->phone = $data['phone'] ?? $data['telefone'] ?? null;

            // Valores padrão para geolocalização se não fornecidos
            $lead->zip_code = $data['zip_code'] ?? $data['cep'] ?? '';
            $lead->city = $data['city'] ?? $data['cidade'] ?? '';
            $lead->state = $data['state'] ?? $data['estado'] ?? '';
            $lead->address = $data['address'] ?? $data['endereco'] ?? '';
            $lead->latitude = $data['latitude'] ?? 0;
            $lead->longitude = $data['longitude'] ?? 0;

            // Campos específicos para rastreamento da origem
            $lead->external_id = $data['lead_id'] ?? $data['id'] ?? null;
            $lead->external_source = 'facebook';
            $lead->status = 'new';
            $lead->is_active = true;

            $lead->save();

            Log::info('Lead do Facebook salvo com sucesso', ['lead_id' => $lead->id]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar lead do Facebook', [
                'erro' => $e->getMessage(),
                'dados' => $data
            ]);
        }
    }

    /**
     * Atualiza um lead existente com dados do Facebook
     */
    private function handleLeadUpdated(array $data): void
    {
        Log::info('Atualização de lead recebida do Facebook', $data);

        try {
            // Tenta encontrar o lead pelo ID externo primeiro
            $lead = null;

            if (!empty($data['lead_id']) || !empty($data['id'])) {
                $externalId = $data['lead_id'] ?? $data['id'];
                $lead = Lead::where('external_id', $externalId)
                    ->where('external_source', 'facebook')
                    ->first();
            }

            // Se não encontrou pelo ID externo, tenta pelo email
            if (!$lead && !empty($data['email'])) {
                $lead = Lead::where('email', $data['email'])->first();
            }

            // Se ainda não encontrou, tenta pelo telefone
            if (!$lead && (!empty($data['phone']) || !empty($data['telefone']))) {
                $phone = $data['phone'] ?? $data['telefone'];
                $lead = Lead::whereHas('phones', function ($query) use ($phone) {
                    $query->where('phone_original', $phone);
                })->first();
            }

            if (!$lead) {
                Log::warning('Lead não encontrado para atualização', ['dados' => $data]);
                return;
            }

            // Atualiza os campos conforme necessário
            if (isset($data['name']) || isset($data['nome'])) {
                $lead->name = $data['name'] ?? $data['nome'];
            }

            if (isset($data['email'])) {
                $lead->email = $data['email'];
            }

            if (isset($data['phone']) || isset($data['telefone'])) {
                $lead->phone = $data['phone'] ?? $data['telefone'];
            }

            // Atualiza geolocalização se fornecida
            if (isset($data['zip_code']) || isset($data['cep'])) {
                $lead->zip_code = $data['zip_code'] ?? $data['cep'];
            }

            if (isset($data['city']) || isset($data['cidade'])) {
                $lead->city = $data['city'] ?? $data['cidade'];
            }

            if (isset($data['state']) || isset($data['estado'])) {
                $lead->state = $data['state'] ?? $data['estado'];
            }

            if (isset($data['address']) || isset($data['endereco'])) {
                $lead->address = $data['address'] ?? $data['endereco'];
            }

            if (isset($data['latitude'])) {
                $lead->latitude = $data['latitude'];
            }

            if (isset($data['longitude'])) {
                $lead->longitude = $data['longitude'];
            }

            $lead->save();

            Log::info('Lead do Facebook atualizado com sucesso', ['lead_id' => $lead->id]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar lead do Facebook', [
                'erro' => $e->getMessage(),
                'dados' => $data
            ]);
        }
    }
}
