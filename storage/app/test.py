import requests
import pandas as pd
import random,string,sys


def generate_random_string(length=15):
    characters = string.ascii_letters + string.digits
    random_string = ''.join(random.choice(characters) for _ in range(length))
    return random_string

def flatten_json(nested_json, exclude_keys=None):
    if exclude_keys is None:
        exclude_keys = []
    out = {}

    def flatten(x, name=''):
        if type(x) is dict:
            for a in x:
                if a not in exclude_keys:
                    flatten(x[a], name + a + '_')
        elif type(x) is list:
            i = 0
            for a in x:
                flatten(a, name + str(i) + '_')
                i += 1
        else:
            out[name[:-1]] = x

    flatten(nested_json)
    return out

def write_to_excel(data, sheet_name, file_path):
    df = pd.DataFrame([data])
    with pd.ExcelWriter(file_path, engine='openpyxl', mode='a') as writer:
        df.to_excel(writer, sheet_name=sheet_name, index=False)

f_name = 'storage/' + sys.argv[1]
file = open(f_name,'r')
data = file.read()
file.close()
random_string = generate_random_string()
excel_file_path = 'storage/' + random_string + '.xlsx'

try:
    f_name = 'storage/' + sys.argv[1]
    file = open(f_name,'r')
    data = file.read()
    if data:

        # General Info
        general_info_keys = ['name', 'address', 'address_2', 'city', 'state', 'zip', 'country', 'overall_rating', 'rating_scale', 'inspection_date', 'primary_type', 'secondary_type']
        general_info = {key: data[key] for key in general_info_keys if key in data}
        write_to_excel(general_info, 'General Info', excel_file_path)
        # Physical conditions and DM
        physical_conditions_dm = flatten_json(data.get('physical_condition', {}))
        write_to_excel(physical_conditions_dm, 'Physical conditions and DM', excel_file_path)

        # Photos
        photos_data = [flatten_json(photo) for photo in data.get('images', [])]
        photos_df = pd.DataFrame(photos_data)
        with pd.ExcelWriter(excel_file_path, engine='openpyxl', mode='a') as writer:
          photos_df.to_excel(writer, sheet_name='Photos', index=False)

        # Rent Roll
        rent_roll = flatten_json(data.get('rent_roll', {}))
        write_to_excel(rent_roll, 'Rent Roll', excel_file_path)

        # Mgmt Interview
        mgmt_interview = flatten_json(data.get('mgmt_interview', {}))
        write_to_excel(mgmt_interview, 'Mgmt Interview', excel_file_path)

        # Multifamily
        multifamily = flatten_json(data.get('multifamily', {}))
        write_to_excel(multifamily, 'Multifamily', excel_file_path)
        # Fannie Mae Addendum
        fannie_mae_addendum = flatten_json(data.get('fannie_mae_assmt', {}))
        write_to_excel(fannie_mae_addendum, 'Fannie Mae Addendum', excel_file_path)

        # Fre Assmt Addendum
        fre_assmt_addendum = flatten_json(data.get('fre_assmt', {}))
        write_to_excel(fre_assmt_addendum, 'Fre Assmt Addendum', excel_file_path)

        # Repairs Verification
        repairs_verification = flatten_json(data.get('repairs_verification', {}))
        write_to_excel(repairs_verification, 'Repairs Verification', excel_file_path)

        # Senior Housing Supplement
        senior_housing_supplement = flatten_json(data.get('senior_supplement', {}))
        write_to_excel(senior_housing_supplement, 'Senior Housing Supplement', excel_file_path)

        # Hospitals
        hospitals = flatten_json(data.get('hospitals', {}))
        write_to_excel(hospitals, 'Hospitals', excel_file_path)

        print(random_string)

