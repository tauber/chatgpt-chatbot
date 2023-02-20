import openai
import time
import pandas as pd
import numpy as np
import csv
import json

class ReadConf:
    def __init__(self, path):
        # Read the config file
        self.keys = dict()
        for line in open(path, 'r'):
            # remove end-of-line
            key, val = line[:-1].split("=")
            self.keys[key] = val

class DocsEmbeddings:
    def __init__(self, path):
        conf = ReadConf(path)
        openai.api_key = conf.keys["OPENAI_API_KEY"]

#        self.model = "text-embedding-ada-002"      # cheapest about 3000 pages per $1
#        self.model = "text-similarity-curie-001"       # one under text-davinci-003, about 60 pages per $1
        self.model = "text-search-curie-doc-001"       # one under text-davinci-003, about 60 pages per $1
    
    # save the available models to a csv.
    def SaveModelsAsCsv(self, csvfile):
        models = openai.Model.list()

        # now we will open a file for writing
        data_file = open(csvfile, 'w')

        # create the csv writer object
        csv_writer = csv.writer(data_file)
        
        # Counter variable used for writing
        # headers to the CSV file
        count = 0
        
        for emp in models.data:
            if count == 0:
        
                # Writing headers of CSV file
                header = emp.keys()
                csv_writer.writerow(header)
                count += 1
        
            # Writing data of CSV file
            csv_writer.writerow(emp.values())
        
        data_file.close()

    # due to the cost of getting embedding, file must be written.
    def GetEmbeddings(self, csv_path, file_in, file_out):
        self.embeddings = pd.DataFrame(columns=["url", "text", "embedding"])
        self.text_df = pd.read_csv(csv_path+file_in)
    
        text_iter = self.text_df.itertuples()
        row = next(text_iter)
        while len(self.embeddings) < len(self.text_df):
            # get the openai embedding
            try:
                response = openai.Embedding.create(
                    input=row.text,
                    model=self.model
                )

                self.embeddings.loc[len(self.embeddings)] = [row.path, row.text, response['data'][0]['embedding']]
                row = next(text_iter)

            except:
                # rate limited to 60 requests per minute, need to wait again
                time.sleep(30)
                print(f"sleeping at iteration {len(self.embeddings)}")
                continue
                   
        self.embeddings.to_csv(csv_path+file_out, index=False)

    def DropSavedIndex(self, csv_path, file_in):
        df = pd.read_csv(csv_path+file_in)
        df.drop(columns='Unnamed: 0', inplace=True)
        df.to_csv(csv_path+file_in, index=False)


    # This function is not necessary for GPT, all embedding vectors are already normalized.
    def SaveVectorMagnitudes(self, csv_path, file_in, file_out):
        df = pd.read_csv(csv_path+file_in)

        # duplicate the embeddings column and apply a map to it.
        df['magnitudes'] = df["embedding"].apply(eval).apply(np.array).apply(np.linalg.norm)

        df.to_csv(csv_path+file_out)

docs = DocsEmbeddings(r'D:\Users\Zohar\Jobs\IDLWeb\chatbot\etc\objectcoded.ca\.env')
#docs.SaveModelsAsCsv(r'D:\Users\Zohar\Jobs\IDLWeb\chatbot\etc\objectcoded.ca\models.csv')
docs.GetEmbeddings(r'D:\Users\Zohar\Jobs\IDLWeb\chatbot\httrack-idlweb\www.idlwebinc.com\\', 'full_site_dataframe.csv', 'full_site_embeddings.csv')
#docs.DropSavedIndex(r'D:\Users\Zohar\Jobs\IDLWeb\chatbot\httrack-idlweb\www.idlwebinc.com\\', 'full_site_embeddings.csv')
#docs.SaveVectorMagnitudes(r'D:\Users\Zohar\Jobs\IDLWeb\chatbot\httrack-idlweb\www.idlwebinc.com\\', 'full_site_embeddings.csv', 'full_site_embeddings_w_mags.csv')
