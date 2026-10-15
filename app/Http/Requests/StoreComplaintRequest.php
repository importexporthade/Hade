<?php

namespace App\Http\Requests;

use App\Enums\ComplaintItemType;
use App\Enums\ComplaintType;
use App\Enums\IdentityDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    /**
     * Anyone may file a complaint; no account is needed.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ComplaintType::class)],
            'consumer_name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::enum(IdentityDocument::class)],
            'document_number' => ['required', 'string', 'max:20', $this->matchesDocumentType(...)],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[\d\s-]{6,20}$/'],
            'email' => ['required', 'email', 'max:255'],
            'is_minor' => ['boolean'],
            'guardian_name' => ['nullable', 'required_if_accepted:is_minor', 'string', 'max:255'],
            'item_type' => ['required', Rule::enum(ComplaintItemType::class)],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'item_description' => ['required', 'string', 'max:255'],
            'detail' => ['required', 'string', 'max:3000'],
            'consumer_request' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Laravel's own messages are in English, so the form spells out its own in Spanish.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Completa :attribute.',
            'required_if_accepted' => 'Completa :attribute.',
            'email' => 'Escribe un correo válido.',
            'max' => ':Attribute es demasiado largo.',
            'amount.max' => 'El monto es demasiado alto.',
            'numeric' => ':Attribute debe ser un número.',
            'min' => ':Attribute no puede ser negativo.',
            'phone.regex' => 'Escribe un teléfono válido.',
            'enum' => 'Elige una opción de :attribute.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'el tipo',
            'consumer_name' => 'tu nombre completo',
            'document_type' => 'el tipo de documento',
            'document_number' => 'el número de documento',
            'address' => 'tu domicilio',
            'phone' => 'tu teléfono',
            'email' => 'tu correo',
            'guardian_name' => 'el nombre del padre, madre o apoderado',
            'item_type' => 'si es producto o servicio',
            'amount' => 'el monto',
            'item_description' => 'la descripción del producto o servicio',
            'detail' => 'el detalle',
            'consumer_request' => 'tu pedido',
        ];
    }

    private function matchesDocumentType(string $attribute, mixed $value, Closure $fail): void
    {
        $document = IdentityDocument::tryFrom((string) $this->input('document_type'));

        if ($document !== null && ! preg_match($document->pattern(), (string) $value)) {
            $fail("El número no es un {$document->getLabel()} válido.");
        }
    }
}
