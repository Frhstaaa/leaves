import Swal from 'sweetalert2';

// Base custom SweetAlert2 configuration with SGIN styling
export const CustomSwal = Swal.mixin({
  customClass: {
    popup: 'rounded-3xl p-6 border border-slate-200 shadow-2xl font-sans',
    title: 'text-base sm:text-lg font-black text-slate-900',
    htmlContainer: 'text-xs font-medium text-slate-600 leading-relaxed mt-2',
    confirmButton: 'px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-lg shadow-emerald-600/20 mr-2 transition-all',
    cancelButton: 'px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all',
    denyButton: 'px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-lg shadow-rose-600/20 transition-all',
  },
  buttonsStyling: false,
});

// Toast notification helper (top-right floating toast)
export const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3500,
  timerProgressBar: true,
  customClass: {
    popup: 'rounded-2xl p-3 shadow-xl border border-slate-200 bg-white font-sans text-xs',
  },
  didOpen: (toast) => {
    toast.onmouseenter = Swal.stopTimer;
    toast.onmouseleave = Swal.resumeTimer;
  }
});

// Helper for Info/Warning/Success/Error Alert Dialogs
export const showAlert = ({ title, text, icon = 'info', confirmText = 'Mengerti' }) => {
  return CustomSwal.fire({
    title,
    text,
    icon,
    confirmButtonText: confirmText,
  });
};

// Helper for Interactive Confirmations (returns boolean)
export const showConfirm = async ({
  title = 'Apakah Anda yakin?',
  text = '',
  icon = 'warning',
  confirmText = 'Ya, Lanjutkan',
  cancelText = 'Batal',
}) => {
  const result = await CustomSwal.fire({
    title,
    text,
    icon,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: cancelText,
    reverseButtons: true,
  });
  return result.isConfirmed;
};

// Helper for Quick Toast Notifications
export const showToast = (message, icon = 'success') => {
  return Toast.fire({
    icon,
    title: message,
  });
};

export default CustomSwal;
