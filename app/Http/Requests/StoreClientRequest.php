<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected $stopOnFirstFailure = true;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'email' => 'required|email|unique:clients|max:100',
            'telephone' => 'required|numeric|unique:clients|max:8',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le champ "Nom" est requis.',
            'nom.max' => 'Le nom est trop long.',

            'prenom.required' => 'Le champ "Prénom" est requis.',
            'prenom.max' => 'Le prénom est trop long.',

            'email.required' => 'Le champ "Email" est requis.',
            'email.email' => 'Veuillez fournir une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'email.max' => 'Cette adresse e-mail est trop long.',

            'telephone.required' => 'Le champ "Téléphone" est requis.',
            'telephone.unique' => 'Ce numero de téléphone est deja utilise.',
            'telephone.max' => 'Le numéro de téléphone doit pas dépasser 8 chiffres.',
            'telephone.numeric' => 'Le numéro de téléphone doit être que des chiffres.',

        ];
    }
}
