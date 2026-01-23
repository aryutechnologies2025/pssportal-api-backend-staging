<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobFormController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RemarkController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactCandidateController;
use App\Http\Controllers\ContractEmployeeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\employee\EmployeeAuthController;
use App\Http\Controllers\employee\EmployeeContractCandidateController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\employee\PssEmployeeAttendanceController;
use App\Http\Controllers\PssWorkShiftController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivitiesController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetSubCategoryController;
use App\Http\Controllers\PssCompanyController;
use App\Http\Controllers\LeadManagementController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\WorkReportController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\BoardingPointController;
use App\Http\Controllers\EductionController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('/login', [AdminController::class, 'login']);

Route::middleware('static.auth')->group(function () {
    //login
    Route::post('/logout', [AdminController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'list']);

    Route::prefix('job-form')->controller(JobFormController::class)->group(function () {
        Route::post('/pss-job-form', 'pssEnquirystore');
        Route::get('/list', 'index');
        Route::delete('/delete/{id}', 'destroy');
        Route::get('/show/{id}', 'show');
        Route::get('/export', 'export');
    });

    //remarks
    Route::prefix('remarks')->group(function () {
        Route::get('/list/{parent_id}', [RemarkController::class, 'index']);   // list by parent
        Route::post('/store', [RemarkController::class, 'store']);             // add
        Route::get('/show/{id}', [RemarkController::class, 'show']);            // get one
        Route::post('/update/{id}', [RemarkController::class, 'update']);        // update
        Route::delete('/delete/{id}', [RemarkController::class, 'destroy']);    // delete
    });

    //role
    Route::controller(RoleController::class)->group(function () {
        Route::prefix('role')->group(function () {
            Route::get('/', 'list')->name('admin.role_list');
            Route::get('/edit/{id}', 'edit_form')->name('admin.role_edit_form');
            Route::post('/create', 'insert')->name('admin.role_insert');
            Route::post('/update/{id}', 'update')->name('admin.role_update');
            Route::delete('/delete', 'delete')->name('admin.role_delete');
        });
    });

    //permission
    Route::controller(RolePermissionController::class)->group(function () {
        Route::prefix('permission')->group(function () {
            Route::get('/', 'list');
            Route::get('/edit/{id}', 'edit');
            Route::post('/create', 'insert');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete', 'delete');
        });
    });

    //company
    Route::controller(CompanyController::class)->group(function () {
        Route::prefix('company')->group(function () {
            Route::get('/', 'index');
            Route::get('/edit/{id}', 'show');
            Route::post('/create', 'store');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/companylist', 'companylist');
        });
    });

    //contract candidate
    Route::controller(ContactCandidateController::class)->group(function () {
        Route::prefix('contract-emp')->group(function () {
            Route::get('/', 'index');
            Route::get('/edit/{id}', 'show');
            Route::post('/create', 'store');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::post('/import', 'import');
            Route::post('/assign-emp-generate', 'getEmpidGenearate');
            Route::post('/move-candidate-emp', 'moveCandidateToEmp');
        });
    });

    //contact emp
    Route::controller(ContractEmployeeController::class)->group(function () {
        Route::prefix('contract-employee')->group(function () {
            Route::get('/', 'index');
            Route::get('/edit/{id}', 'show');
            Route::post('/create', 'store');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::post('/import', 'import');
            Route::post('/assign-emp-generate', 'getEmpidGenearate');
        });
    });

    //employee
    Route::prefix('employees')->group(function () {
        Route::post('/create', [EmployeeController::class, 'store']);
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/edit/{id}', [EmployeeController::class, 'show']);
        Route::post('/update/{id}', [EmployeeController::class, 'update']);
        Route::delete('/delete/{id}', [EmployeeController::class, 'destroy']);
        Route::post('/job-referal/{id}', [EmployeeController::class, 'jobreferalupdate']);
        Route::post('/assign-company/{id}', [EmployeeController::class, 'assigncompany']);
        Route::post('/assign-emp-generate', [EmployeeController::class, 'getEmpidGenearate']);
        Route::post('/changepassword', [EmployeeController::class, 'changepassword']);
    });

    //contact
    Route::prefix('contact')->group(function () {
        Route::post('/create', [ContactController::class, 'store']);
        Route::get('/list', [ContactController::class, 'index']);
        Route::delete('/delete/{id}', [ContactController::class, 'destroy']);
        Route::get('/edit/{id}', [ContactController::class, 'show']);
    });

    //attendance
    Route::prefix('attendance')->group(function () {
        Route::post('/create', [AttendanceController::class, 'store']);
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('/edit/{id}', [AttendanceController::class, 'show']);
        Route::post('/update/{id}', [AttendanceController::class, 'update']);
        Route::delete('/delete/{id}', [AttendanceController::class, 'destroy']);
        Route::get('company/{company_id}/employees', [AttendanceController::class, 'getCompanyEmployees']);
        Route::post('/import', [AttendanceController::class, 'import']);
    });

    //department
    Route::controller(DepartmentController::class)->group(function () {
        Route::prefix('department')->group(function () {
            Route::get('/', 'list')->name('admin.department_list');
            Route::get('/edit/{id}', 'edit_form')->name('admin.department_edit_form');
            Route::post('/create', 'insert')->name('admin.department_insert');
            Route::post('/update/{id}', 'update')->name('admin.department_update');
            Route::delete('/delete', 'delete')->name('admin.department_delete');
        });
    });

    //branchs
    Route::controller(BranchController::class)->group(function () {
        Route::prefix('branches')->group(function () {
            Route::get('/', 'index')->name('admin.branch_list');
            Route::get('/edit/{id}', 'edit_form')->name('admin.branch_edit_form');
            Route::post('/create', 'store')->name('admin.branch_insert');
            Route::post('/update/{id}', 'update')->name('admin.branch_update');
            Route::delete('/delete', 'delete')->name('admin.branch_delete');
        });
    });

    // pss company
    Route::controller(PssCompanyController::class)->group(function() {
        Route::prefix('pss-company')->group(function() {
            Route::get('/', 'index')->name('admin.pss_company_list');
            Route::get('/edit/{id}', 'edit_form')->name('admin.pss_company_edit_form');
            Route::post('/create', 'store')->name('admin.pss_company_insert');
            Route::post('update/{id}', 'update')->name('admin.pss_company_update');
            Route::delete('/delete', 'delete')->name('admin.pss_company_delete');

            Route::get('/{id}/branches', [PssCompanyController::class, 'companyWithBranches']);
        });
    });

    //shifts
    Route::controller(PssWorkShiftController::class)->group(function () {
        Route::prefix('shifts')->group(function () {
            Route::get('/', 'index');
            Route::get('/show/{id}', 'show');
            Route::post('/create', 'store');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/activeshift', 'activeshift');
        });
    });

    Route::get('settings', [SettingController::class, 'index']);
    Route::post('settings', [SettingController::class, 'store']);
    Route::get('activity', [ActivitiesController::class, 'activities']);

    Route::prefix('lead-management')->group(function () {
        Route::get('/', [LeadManagementController::class, 'index']);      // List
        Route::post('/create', [LeadManagementController::class, 'store']);     // Add
        Route::get('/edit/{id}', [LeadManagementController::class, 'show']);    // View
        Route::post('/update/{id}', [LeadManagementController::class, 'update']);  // Update
        Route::delete('/delete/{id}', [LeadManagementController::class, 'destroy']); // Delete (soft)
        Route::post('/import', [LeadManagementController::class, 'import']);
        Route::post('/status-update/{id}', [LeadManagementController::class, 'statusUpdate']);
        Route::post('/status-list/{id}', [LeadManagementController::class, 'statusList']);
    });

    //attendance report
    Route::controller(AttendanceReportController::class)->group(function () {
        Route::prefix('attendance-report')->group(function () {
            Route::get('/', 'index');
            Route::get('/attendance', 'daywiseAttendanceReport');
        });
    });

    // Asset Category
    Route::controller(AssetCategoryController::class)->group(function () {
        Route::prefix('asset-category')->group(function () {
            Route::get('/', 'index');
            Route::get('/edit/{id}', 'show');
            Route::post('/create', 'store');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
    });

    // Asset Sub Category
    Route::controller(AssetSubCategoryController::class)->group(function () {
        Route::prefix('asset-sub-category')->group(function () {
            Route::get('/', 'index');
            Route::get('/edit/{id}', 'show');
            Route::post('/create', 'store');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
    });

    // Assets
    Route::controller(AssetController::class)->group(function () {
        Route::prefix('assets')->group(function () {
            Route::get('/', 'index');
            Route::get('/edit/{id}', 'show');
            Route::post('/create', 'store');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
    });

    // Work Reports
    Route::controller(WorkReportController::class)->group(function() {
        Route::prefix('work-reports')->group(function() {
            Route::get('/', 'index');
            Route::get('/filter', 'filterByDate');
            Route::get('/edit/{id}', 'show');
            Route::post('/create', 'store');
            Route::put('/update/{id}', 'update');
        });
    });

    // Holidays 
    Route::controller(HolidayController::class)->group(function() {
        Route::prefix('holiday')->group(function() {
            Route::get('/', 'index');
            Route::get('/edit/{id}', 'show');
            Route::post('/create', 'store');
            Route::put('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
    });

    //Bording point
    Route::controller(BoardingPointController::class)->group(function () {
        Route::prefix('boarding-points')->group(function () {
            Route::get('/', 'list')->name('admin.boarding_point_list');
            Route::get('/edit/{id}', 'edit_form')->name('admin.boarding_point_edit_form');
            Route::post('/create', 'insert')->name('admin.boarding_point_insert');
            Route::post('/update/{id}', 'update')->name('admin.boarding_point_update');
            Route::delete('/delete', 'delete')->name('admin.boarding_point_delete');
        });
    });

    //Eduction
    Route::controller(EductionController::class)->group(function () {
        Route::prefix('eduction')->group(function () {
            Route::get('/', 'list')->name('admin.eduction_list');
            Route::get('/edit/{id}', 'edit_form')->name('admin.eduction_edit_form');
            Route::post('/create', 'insert')->name('admin.eduction_insert');
            Route::post('/update/{id}', 'update')->name('admin.eduction_update');
            Route::delete('/delete', 'delete')->name('admin.eduction_delete');
        });
    });
});

Route::prefix('employee')->group(function () {
    Route::post('/login', [EmployeeAuthController::class, 'login']);
    Route::middleware('employee.auth')->group(function () {
        Route::post('/logout', [EmployeeAuthController::class, 'logout']);

        //contract emp
        Route::controller(EmployeeContractCandidateController::class)->group(function () {
            Route::prefix('contract-emp')->group(function () {
                Route::get('/', 'list');
                Route::get('/attendance', 'index');
                Route::get('/contractemplist', 'contractemplist');
                Route::get('/companylist', 'companylist');
            });
        });

        Route::controller(PssEmployeeAttendanceController::class)->group(function () {
            Route::prefix('emp-attendance')->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'store');
            });
        });

         Route::controller(WorkReportController::class)->group(function () {
            Route::prefix('work-report')->group(function () {
                Route::get('/', 'employeeWorkReport');
            });
        });

        Route::get('activity', [ActivitiesController::class, 'empActivities']);

        // Finance
        Route::controller(FinanceController::class)->group(function () {
            Route::prefix('finance')->group(function () {
                Route::get('/', 'index');
                Route::post('/create', 'store');
                Route::post('/update/{id}', 'update');
            });
        });
    });
});