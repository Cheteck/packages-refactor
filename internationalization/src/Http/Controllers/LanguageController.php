<?php

namespace IJIDeals\Internationalization\Http\Controllers;

use IJIDeals\Internationalization\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Language Management",
 *     description="APIs for managing supported languages."
 * )
 */
class LanguageController extends Controller
{
    /**
     * List all languages.
     *
     * @OA\Get(
     *     path="/api/internationalization/languages",
     *     operationId="listLanguages",
     *     summary="List all languages",
     *     tags={"Language Management"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Language")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return Language::all();
    }

    /**
     * Show a single language.
     *
     * @OA\Get(
     *     path="/api/internationalization/languages/{id}",
     *     operationId="getLanguageById",
     *     summary="Get details of a specific language",
     *     tags={"Language Management"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the language to retrieve",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Language")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Language not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Internationalization\\Models\\Language] 1")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        return Language::findOrFail($id);
    }

    /**
     * Create a new language.
     *
     * @OA\Post(
     *     path="/api/internationalization/languages",
     *     operationId="createLanguage",
     *     summary="Create a new language",
     *     tags={"Language Management"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Language data to store",
     *
     *         @OA\JsonContent(
     *             required={"code", "name", "direction"},
     *
     *             @OA\Property(property="code", type="string", example="fr", description="The ISO code (e.g., en, fr)"),
     *             @OA\Property(property="name", type="string", example="French", description="The language name (e.g., English, Français)"),
     *             @OA\Property(property="is_default", type="boolean", example=false, description="Is this the default language?"),
     *             @OA\Property(property="direction", type="string", enum={"ltr", "rtl"}, example="ltr", description="Text direction (left-to-right or right-to-left)"),
     *             @OA\Property(property="status", type="boolean", example=true, description="Is the language active?"),
     *             @OA\Property(property="flag_icon", type="string", nullable=true, example="flag-icon-fr", description="CSS class for a flag icon")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Language created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Language")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:5', 'unique:languages,code', 'regex:/^[a-z]{2,5}$/i'],
            'name' => ['required', 'string'],
            'is_default' => ['boolean'],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'status' => ['boolean'],
            'flag_icon' => ['nullable', 'string'],
        ]);

        if (! empty($validated['is_default'])) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        $language = Language::create($validated);

        return response()->json($language, 201);
    }

    /**
     * Update a language.
     *
     * @OA\Put(
     *     path="/api/internationalization/languages/{id}",
     *     operationId="updateLanguage",
     *     summary="Update an existing language",
     *     tags={"Language Management"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the language to update",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Language data to update",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="code", type="string", example="en", description="The ISO code"),
     *             @OA\Property(property="name", type="string", example="English (Updated)", description="The language name"),
     *             @OA\Property(property="is_default", type="boolean", example=true, description="Set as default language?"),
     *             @OA\Property(property="direction", type="string", enum={"ltr", "rtl"}, example="ltr", description="Text direction"),
     *             @OA\Property(property="status", type="boolean", example=false, description="Is the language active?"),
     *             @OA\Property(property="flag_icon", type="string", nullable=true, example="flag-icon-gb", description="CSS class for a flag icon")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Language updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Language")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Language not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Internationalization\\Models\\Language] 1")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $language = Language::findOrFail($id);
        $validated = $request->validate([
            'code' => ['string', 'max:5', Rule::unique('languages', 'code')->ignore($language->id), 'regex:/^[a-z]{2,5}$/i'],
            'name' => ['string'],
            'is_default' => ['boolean'],
            'direction' => [Rule::in(['ltr', 'rtl'])],
            'status' => ['boolean'],
            'flag_icon' => ['nullable', 'string'],
        ]);

        if (! empty($validated['is_default'])) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        $language->update($validated);

        return response()->json($language);
    }

    /**
     * Delete a language.
     *
     * @OA\Delete(
     *     path="/api/internationalization/languages/{id}",
     *     operationId="deleteLanguage",
     *     summary="Delete a language",
     *     tags={"Language Management"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the language to delete",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Language deleted successfully (No Content)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Deleted")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Language not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Internationalization\\Models\\Language] 1")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $language = Language::findOrFail($id);
        $language->delete();

        return response()->json(['message' => 'Deleted'], 204);
    }
}
