// toast.js - simple toast notifications with SweetAlert2 integration
(function(){
  // Original toast function for simple notifications
  window.showToast = function(msg, type='info') {
    const root = document.getElementById('toast-root');
    if(!root) return;
    const el = document.createElement('div');
    const colors = {
      info: 'bg-blue-600',
      success: 'bg-green-600',
      error: 'bg-red-600',
      warning: 'bg-amber-500'
    };
    el.className = (colors[type]||colors.info)+ ' text-white text-sm px-4 py-2 rounded shadow flex items-center gap-2 animate-fade';
    el.textContent = msg;
    root.appendChild(el);
    setTimeout(()=>{ el.classList.add('opacity-0','transition'); setTimeout(()=> el.remove(), 500); }, 3000);
  };

  // SweetAlert2 wrapper functions
  window.showAlert = function(title, text, icon = 'info') {
    return Swal.fire({
      title: title,
      text: text,
      icon: icon,
      confirmButtonColor: '#2563eb',
      customClass: {
        popup: 'font-[Inter]'
      }
    });
  };

  window.showConfirm = function(title, text, confirmText = 'Yes', cancelText = 'Cancel') {
    return Swal.fire({
      title: title,
      text: text,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      customClass: {
        popup: 'font-[Inter]'
      }
    });
  };

  window.showSuccess = function(title, text = '') {
    return Swal.fire({
      title: title,
      text: text,
      icon: 'success',
      confirmButtonColor: '#16a34a',
      customClass: {
        popup: 'font-[Inter]'
      }
    });
  };

  window.showError = function(title, text = '', errorCode = null, debugInfo = null) {
    let content = text;
    
    // Add error code if provided
    if (errorCode) {
      content += `\n\nError Code: ${errorCode}`;
    }
    
    // Add debug info if provided and in development mode
    if (debugInfo && window.APP_DEBUG) {
      content += `\n\nDebug Info: ${debugInfo}`;
    }
    
    return Swal.fire({
      title: title,
      text: content,
      icon: 'error',
      confirmButtonColor: '#dc2626',
      customClass: {
        popup: 'font-[Inter]'
      },
      footer: errorCode ? `<small>Error Code: ${errorCode}</small>` : undefined
    });
  };

  window.showWarning = function(title, text = '') {
    return Swal.fire({
      title: title,
      text: text,
      icon: 'warning',
      confirmButtonColor: '#ea580c',
      customClass: {
        popup: 'font-[Inter]'
      }
    });
  };

  // Enhanced toast with SweetAlert2 option
  window.showNotification = function(msg, type='info', useSwal = false) {
    if (useSwal && window.Swal) {
      const iconMap = {
        info: 'info',
        success: 'success', 
        error: 'error',
        warning: 'warning'
      };
      
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
          popup: 'font-[Inter]'
        },
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
      });
      
      return Toast.fire({
        icon: iconMap[type] || 'info',
        title: msg
      });
    } else {
      return showToast(msg, type);
    }
  };

  // Enhanced error handler with detailed debugging
  window.showDetailedError = function(errorType, errorMessage, errorDetails = {}) {
    const errorCodes = {
      'LOGIN_FAILED': 'L001',
      'INVALID_CREDENTIALS': 'L002', 
      'INVALID_PAGEANT_CODE': 'L003',
      'USER_INACTIVE': 'L004',
      'PERMISSION_DENIED': 'L005',
      'DATABASE_ERROR': 'DB001',
      'VALIDATION_ERROR': 'V001',
      'NETWORK_ERROR': 'N001',
      'SESSION_EXPIRED': 'S001',
      'FORM_SUBMISSION_ERROR': 'F001'
    };

    const errorDescriptions = {
      'LOGIN_FAILED': 'Authentication failed. Please check your credentials.',
      'INVALID_CREDENTIALS': 'The username or password you entered is incorrect.',
      'INVALID_PAGEANT_CODE': 'The pageant code you entered is not valid or does not exist.',
      'USER_INACTIVE': 'Your account has been deactivated. Please contact an administrator.',
      'PERMISSION_DENIED': 'You do not have permission to access this resource.',
      'DATABASE_ERROR': 'A database error occurred. Please try again later.',
      'VALIDATION_ERROR': 'The data you entered is not valid. Please check and try again.',
      'NETWORK_ERROR': 'A network error occurred. Please check your connection.',
      'SESSION_EXPIRED': 'Your session has expired. Please log in again.',
      'FORM_SUBMISSION_ERROR': 'There was an error submitting the form. Please try again.'
    };

    const errorCode = errorCodes[errorType] || 'E999';
    const description = errorDescriptions[errorType] || errorMessage;
    
    let debugContent = '';
    if (window.APP_DEBUG && Object.keys(errorDetails).length > 0) {
      debugContent = '\n\nDebug Details:\n' + JSON.stringify(errorDetails, null, 2);
    }

    return Swal.fire({
      title: 'Error Occurred',
      html: `
        <div class="text-left">
          <p class="mb-2"><strong>Description:</strong> ${description}</p>
          <p class="mb-2"><strong>Error Code:</strong> ${errorCode}</p>
          ${window.APP_DEBUG && debugContent ? `<details class="mt-4"><summary class="cursor-pointer text-sm text-gray-600">Debug Information</summary><pre class="text-xs bg-gray-100 p-2 mt-2 rounded text-left overflow-auto">${JSON.stringify(errorDetails, null, 2)}</pre></details>` : ''}
        </div>
      `,
      icon: 'error',
      confirmButtonColor: '#dc2626',
      customClass: {
        popup: 'font-[Inter]',
        htmlContainer: 'text-sm'
      },
      footer: `<small class="text-gray-500">Error Code: ${errorCode} | ${new Date().toLocaleTimeString()}</small>`
    });
  };
})();
