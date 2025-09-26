import AdminLayout from '../shared/AdminLayout';

export default function Awards() {
  return (
    <AdminLayout title="Awards Management" description="Manage award categories and winners">
      <div className="text-center py-12">
        <h3 className="text-xl font-semibold mb-4">Awards Management</h3>
        <p className="text-gray-600">Award categories and winner selection interface</p>
      </div>
    </AdminLayout>
  );
}