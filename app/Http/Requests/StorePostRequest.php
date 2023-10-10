<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'email' => 'required|email|unique:users|max:100',
            'password' => 'required|min:8',
            'adresse' => 'required',
            'telephone1' => 'required|numeric|unique:users|max:8',
            'telephone2' => 'numeric|unique:users|max:8',
            'qualification' => 'required',
            'experience' => 'required',
            'description' => 'required',
            'image1'    => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image2'    => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image3'    => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'domaine_id' => 'required',
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

            'password.require' => 'Veuillez fournir un mot de passe.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 Caractères.',

            'adresse.required' => 'Le champ "Adresse" est requis.',

            'telephone1.required' => 'Le champ "Téléphone" est requis.',
            'telephone1.unique' => 'Ce numero de téléphone est deja utilise.',
            'telephone1.max' => 'Le numéro de téléphone doit pas dépasser 8 chiffres.',
            'telephone1.numeric' => 'Le numéro de téléphone doit être que des chiffres.',

            'telephone2.unique' => 'Ce numero de téléphone est deja utilise.',
            'telephone2.max' => 'Le numéro de téléphone doit pas dépasser 8 chiffres.',
            'telephone2.numeric' => 'Le numéro de téléphone doit être que des chiffres.',

            'qualification.required' => 'Le champ "Qualification" est requis.',

            'experience.required' => 'Le champ "Experience" est requis.',

            'description.required' => 'Le champ "Description" est requis.',

            'image1.image' => 'Image 1 doit être une image.',
            'image1.mines' => 'Image 1 doit être un fichier de type jpeg,png,jpg,gif,svg.',
            'image1.max' => 'La taille de image 1 doit pas dépasser 2048 ko',

            'image2.image' => 'Image 1 doit être une image.',
            'image2.mines' => 'Image 1 doit être un fichier de type jpeg,png,jpg,gif,svg.',
            'image2.max' => 'La taille de image 1 doit pas dépasser 2048 ko',

            'image3.image' => 'Image 1 doit être une image.',
            'image3.mines' => 'Image 1 doit être un fichier de type jpeg,png,jpg,gif,svg.',
            'image3.max' => 'La taille de image 1 doit pas dépasser 2048 ko',

            'domaine_id.required' => 'Le champ "Domaine" est requis.',
        ];
    }
}
