<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Rules\CustomRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Articles extends Controller
{
    public function getArticles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);
        return response()->json([]);
    }

    public function index()
    {
        return response()->json(['ШАЛОМ']);
    }

    private function getBrandId($brand_name)
    {
        $brand_id = null;
        $brand = Brand::where('brand_name', $brand_name)->first();
        if ($brand) {
            $brand_id = $brand->id;
        } else {
            $new_brand = Brand::create(['brand_name' => $brand_name]);
            $brand_id = $new_brand->id;
        }
        return $brand_id;
    }

    private function getCategory($category_name)
    {
        $category_id = null;
        if ($category_name == null || $category_name == '') {
            return null;
        }
        $category = Category::where('category_name', $category_name)->first();
        if ($category) {
            $category_id = $category->id;
        } else {
            $new_cat = Category::create(['category_name' => $category_name]);
            $category_id = $new_cat->id;
        }
        return $category_id;
    }

    public function uploadArticles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:xlsx', 'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'max:5120', new CustomRule()]
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $written_qty = 0;
        $duplicates = 0;
        $bad_articles = 0;
        $file = $request->file('file')->store('prices');
        $file_path = storage_path('app/private/' . $file);
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file_path);
        $written_articles = [];
        $brand_cache = [];
        $category_cache = [];
        $batch_to_write = [];
        foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $key => $rowData) {
            $cellIterator = $rowData->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            if ($key == 1) {
                continue;
            }
            $row = [];
            foreach ($cellIterator as $cell) {
                $row[] = $cell->getValue();
            }
            if ($row[10] == null) {
                array_pop($row);
            } else {
                array_shift($row);
            }
            if (array_key_exists($row[5], $written_articles)) {
                $duplicates++;
                continue;
            }
            $flag = false;
            foreach ($row as $index => $value) {
                if (in_array($index, [3, 5, 7, 8, 9])) {
                    if ($value == null) {
                        $flag = true;
                        break;
                    }
                }
            }
            if ($flag) {
                $bad_articles++;
                continue;
            }
            $brand_id = null;
            $first_cat_id = null;
            $second_cat_id = null;
            $catalog = null;
            if (array_key_exists($row[3], $brand_cache)) {
                $brand_id = $brand_cache[$row[3]];
            } else {
                $brand_id = $this->getBrandId($row[3]);
                $brand_cache[$row[3]] = $brand_id;
            }

            if (array_key_exists($row[0], $category_cache)) {
                $first_cat_id = $category_cache[$row[0]];
            } else {
                $first_cat_id = $this->getCategory($row[0]);
                $category_cache[$row[0]] = $first_cat_id;
            }

            if (array_key_exists($row[1], $category_cache)) {
                $second_cat_id = $category_cache[$row[1]];
            } else {
                $second_cat_id = $this->getCategory($row[1]);
                $category_cache[$row[1]] = $second_cat_id;
            }

            if (array_key_exists($row[2], $category_cache)) {
                $catalog = $category_cache[$row[2]];
            } else {
                $catalog = $this->getCategory($row[2]);
                $category_cache[$row[2]] = $catalog;
            }

            $written_articles[$row[5]] = true;
            $obj_to_write = [
                'code' => $row[5],
                'brand_id' => $brand_id,
                'name' => $row[4],
                'description' => $row[6],
                'price' => $row[7],
                'warranty' => $row[8] == 'Нет' ? 0 : 1,
                'category_id' => $first_cat_id,
                'second_category_id' => $second_cat_id,
                'catalog_id' => $catalog,
                'availability' => $row[9] == 'есть в наличие' ? 1 : 0
            ];
            $batch_to_write[] = $obj_to_write;
            $written_qty++;
            if (count($batch_to_write) > 100) {
                Article::insertOrIgnore($batch_to_write);
                $batch_to_write = [];
            }
        }
        if (count($batch_to_write) > 0) {
            Article::insertOrIgnore($batch_to_write);
        }
        Storage::delete($file);
        return response()->json(['message' => 'File uploaded successfully', 'file' => $file, 'bad_articles' => $bad_articles,
            'written_qty' => $written_qty, 'duplicates' => $duplicates, 'overall_qty' => $written_qty + $duplicates + $bad_articles]);
    }
}
