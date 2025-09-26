import AdminLayout from '../shared/AdminLayout';

export default function FinalRound() {
  return (
    <AdminLayout title="Final Round Control" description="Manage the final round of competition">
      <div className="text-center py-12">
        <h3 className="text-xl font-semibold mb-4">Final Round Management</h3>
        <p className="text-gray-600">Final round controls and monitoring interface</p>
      </div>
    </AdminLayout>
  );
}