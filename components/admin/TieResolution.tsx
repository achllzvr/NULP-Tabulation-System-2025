import AdminLayout from '../shared/AdminLayout';

export default function TieResolution() {
  return (
    <AdminLayout title="Tie Resolution" description="Resolve scoring ties and conflicts">
      <div className="text-center py-12">
        <h3 className="text-xl font-semibold mb-4">Tie Resolution</h3>
        <p className="text-gray-600">Interface for resolving tied scores</p>
      </div>
    </AdminLayout>
  );
}